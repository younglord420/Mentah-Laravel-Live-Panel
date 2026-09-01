<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\ManualIpBlacklist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotIpBlocklistService
{
    public const CACHE_IPS = 'bot_ip_blocklist:ips';

    public const CACHE_CIDRS = 'bot_ip_blocklist:cidrs';

    public const MYIP_MS_URL = 'https://myip.ms/files/bots/live_webcrawlers.txt';

    public const VASTEL_URL = 'https://raw.githubusercontent.com/antoinevastel/avastel-bot-ips-lists/master/avastel-proxy-bot-ips-blocklist-5days.txt';

    /**
     * @return array{ips:int,cidrs:int,total:int}
     */
    public function sync(): array
    {
        /** @var array<string, string> $ips */
        $ips = [];
        /** @var list<array{cidr:string,label:string}> $cidrs */
        $cidrs = [];

        if (Setting::bool('block_bot_ip_myip_ms', true)) {
            foreach ($this->fetchMyipMs() as $ip) {
                $ips[$ip] = 'myip.ms';
            }
        }

        if (Setting::bool('block_bot_ip_vastel', true)) {
            foreach ($this->fetchVastelCidrs() as $cidr) {
                $cidrs[] = ['cidr' => $cidr, 'label' => 'avastel-5day'];
            }
        }

        foreach (ManualIpBlacklist::all() as $ip) {
            $ips[$ip] = 'custom';
        }

        Cache::put(self::CACHE_IPS, $ips, now()->addHours(12));
        Cache::put(self::CACHE_CIDRS, $cidrs, now()->addHours(12));

        $ipCount = count($ips);
        $cidrCount = count($cidrs);

        Setting::setMany([
            'block_bot_ip_count' => (string) $ipCount,
            'block_bot_ip_cidr_count' => (string) $cidrCount,
            'block_bot_ip_synced_at' => now()->toDateTimeString(),
        ]);

        return [
            'ips' => $ipCount,
            'cidrs' => $cidrCount,
            'total' => $ipCount + $cidrCount,
        ];
    }

    public function match(string $ip): ?string
    {
        if ($ip === '' || app(IpIntelService::class)->isPrivateIp($ip)) {
            return null;
        }

        $this->ensureLoaded();

        /** @var array<string, string> $ips */
        $ips = Cache::get(self::CACHE_IPS, []);

        if (isset($ips[$ip])) {
            return 'IP blocklist ('.$ips[$ip].'): '.$ip;
        }

        /** @var list<array{cidr:string,label:string}> $cidrs */
        $cidrs = Cache::get(self::CACHE_CIDRS, []);

        foreach ($cidrs as $row) {
            if ($this->ipInCidr($ip, $row['cidr'])) {
                return 'IP blocklist ('.$row['label'].'): '.$row['cidr'];
            }
        }

        foreach (ManualIpBlacklist::all() as $extraIp) {
            if ($ip === $extraIp) {
                return 'IP blocklist (custom): '.$ip;
            }
        }

        return null;
    }

    protected function ensureLoaded(): void
    {
        if (Cache::has(self::CACHE_IPS) || Cache::has(self::CACHE_CIDRS)) {
            return;
        }

        try {
            $this->sync();
        } catch (\Throwable $e) {
            Log::warning('bot_ip_blocklist_lazy_sync_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return list<string>
     */
    protected function fetchMyipMs(): array
    {
        try {
            $res = Http::timeout(60)->get(self::MYIP_MS_URL);
            if (! $res->ok()) {
                return [];
            }

            $ips = [];
            foreach (preg_split('/\r\n|\r|\n/', $res->body()) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})/', $line, $m)) {
                    $ip = $m[1];
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ips[] = $ip;
                    }
                }
            }

            return array_values(array_unique($ips));
        } catch (\Throwable $e) {
            Log::warning('myip_ms_fetch_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    protected function fetchVastelCidrs(): array
    {
        try {
            $res = Http::timeout(30)->get(self::VASTEL_URL);
            if (! $res->ok()) {
                return [];
            }

            $cidrs = [];
            $headerPassed = false;

            foreach (preg_split('/\r\n|\r|\n/', $res->body()) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                if (! $headerPassed) {
                    $headerPassed = true;

                    continue;
                }

                $parts = explode(';', $line);
                $cidr = trim($parts[0] ?? '');
                if ($cidr !== '' && $this->isValidCidrOrIp($cidr)) {
                    $cidrs[] = $cidr;
                }
            }

            return array_values(array_unique($cidrs));
        } catch (\Throwable $e) {
            Log::warning('vastel_fetch_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    protected function extraIps(): array
    {
        return ManualIpBlacklist::all();
    }

    protected function isValidCidrOrIp(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (! str_contains($value, '/')) {
            return false;
        }

        [$ip, $mask] = explode('/', $value, 2);
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $mask = (int) $mask;

        return $mask >= 0 && $mask <= 32;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || ! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask));

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
