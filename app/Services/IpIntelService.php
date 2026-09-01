<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpIntelService
{
    /**
     * Unified IP intelligence.
     *
     * @return array{
     *   isp: ?string,
     *   org: ?string,
     *   asn: ?string,
     *   country: ?string,
     *   country_code: ?string,
     *   city: ?string,
     *   is_vpn: bool,
     *   is_proxy: bool,
     *   is_tor: bool,
     *   is_datacenter: bool,
     *   is_abuser: bool,
     *   abuse_score: int,
     *   abuse_reports: int,
     *   source: string
     * }
     */
    public function lookup(string $ip): array
    {
        $empty = $this->emptyResult('none');

        if ($ip === '' || $this->isPrivateIp($ip)) {
            return array_merge($this->emptyResult('local'), [
                'isp' => 'Local/Private Network',
                'org' => 'Local',
                'country' => 'Local',
                'country_code' => 'LO',
            ]);
        }

        return Cache::remember("ipintel:v3:{$ip}", now()->addHours(12), function () use ($ip, $empty) {
            $base = $this->lookupPrimary($ip) ?? $this->lookupProxyCheck($ip) ?? $empty;

            if (Setting::bool('abuseipdb_enabled') && filled(Setting::get('abuseipdb_api_key'))) {
                $abuse = $this->lookupAbuseIpdb($ip);
                if ($abuse !== null) {
                    $base['abuse_score'] = $abuse['score'];
                    $base['abuse_reports'] = $abuse['total_reports'];
                    $base['is_abuser'] = $base['is_abuser'] || $abuse['total_reports'] > 0;
                    if (! filled($base['isp']) && filled($abuse['isp'])) {
                        $base['isp'] = $abuse['isp'];
                    }
                    if (! filled($base['country_code']) && filled($abuse['country_code'])) {
                        $base['country_code'] = $abuse['country_code'];
                    }
                }
            }

            return $base;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function lookupPrimary(string $ip): ?array
    {
        $key = trim((string) Setting::get('ipapi_is_api_key', ''));
        if ($key === '') {
            return null;
        }

        try {
            $res = Http::timeout(4)
                ->withHeaders(['Accept' => 'application/json'])
                ->get('https://api.ipapi.is/', [
                    'q' => $ip,
                    'key' => $key,
                ]);

            if (! $res->ok()) {
                return null;
            }

            $data = $res->json();
            if (! is_array($data) || isset($data['error'])) {
                return null;
            }

            $asn = null;
            if (isset($data['asn_num'])) {
                $asn = 'AS'.(int) $data['asn_num'];
            }

            return [
                'isp' => $data['company_name'] ?? ($data['asn_org'] ?? null),
                'org' => $data['asn_org'] ?? ($data['company_name'] ?? null),
                'asn' => $asn,
                'country' => $data['country'] ?? null,
                'country_code' => $data['cc'] ?? ($data['country_code'] ?? null),
                'city' => $data['city'] ?? null,
                'is_vpn' => (bool) ($data['is_vpn'] ?? false),
                'is_proxy' => (bool) ($data['is_proxy'] ?? false),
                'is_tor' => (bool) ($data['is_tor'] ?? false),
                'is_datacenter' => (bool) ($data['is_datacenter'] ?? false),
                'is_abuser' => (bool) ($data['is_abuser'] ?? false),
                'abuse_score' => 0,
                'source' => 'ipapi.is',
            ];
        } catch (\Throwable $e) {
            Log::warning('ipapi.is lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function lookupProxyCheck(string $ip): ?array
    {
        $key = trim((string) Setting::get('proxycheck_api_key', ''));
        if ($key === '') {
            return null;
        }

        try {
            $res = Http::timeout(4)->get("http://proxycheck.io/v2/{$ip}", [
                'key' => $key,
                'vpn' => 1,
                'asn' => 1,
                'risk' => 1,
            ]);

            if (! $res->ok()) {
                return null;
            }

            $json = $res->json();
            if (($json['status'] ?? '') === 'error') {
                return null;
            }

            $row = $json[$ip] ?? null;
            if (! is_array($row)) {
                return null;
            }

            $proxy = strtolower((string) ($row['proxy'] ?? 'no')) === 'yes';
            $type = strtolower((string) ($row['type'] ?? ''));
            $isVpn = $proxy && str_contains($type, 'vpn');
            $isTor = str_contains($type, 'tor');
            $asn = isset($row['asn']) ? (string) $row['asn'] : null;
            if ($asn && ! str_starts_with(strtoupper($asn), 'AS')) {
                $asn = 'AS'.$asn;
            }

            return [
                'isp' => $row['provider'] ?? ($row['organisation'] ?? null),
                'org' => $row['organisation'] ?? ($row['provider'] ?? null),
                'asn' => $asn,
                'country' => $row['country'] ?? null,
                'country_code' => $row['isocode'] ?? null,
                'city' => $row['city'] ?? null,
                'is_vpn' => $isVpn || $proxy,
                'is_proxy' => $proxy,
                'is_tor' => $isTor,
                'is_datacenter' => str_contains($type, 'hosting') || str_contains($type, 'comp'),
                'is_abuser' => false,
                'abuse_score' => (int) ($row['risk'] ?? 0),
                'source' => 'proxycheck',
            ];
        } catch (\Throwable $e) {
            Log::warning('proxycheck lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{total_reports:int,score:int,isp:?string,country_code:?string}|null
     */
    protected function lookupAbuseIpdb(string $ip): ?array
    {
        $key = trim((string) Setting::get('abuseipdb_api_key', ''));
        if ($key === '') {
            return null;
        }

        try {
            $res = Http::timeout(4)
                ->withHeaders([
                    'Key' => $key,
                    'Accept' => 'application/json',
                ])
                ->get('https://api.abuseipdb.com/api/v2/check', [
                    'ipAddress' => $ip,
                    'maxAgeInDays' => 90,
                    'verbose' => '',
                ]);

            if (! $res->ok()) {
                return null;
            }

            $data = $res->json('data') ?? [];

            return [
                'total_reports' => (int) ($data['totalReports'] ?? 0),
                'score' => (int) ($data['abuseConfidenceScore'] ?? 0),
                'isp' => $data['isp'] ?? null,
                'country_code' => $data['countryCode'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('AbuseIPDB lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(string $source): array
    {
        return [
            'isp' => null,
            'org' => null,
            'asn' => null,
            'country' => null,
            'country_code' => null,
            'city' => null,
            'is_vpn' => false,
            'is_proxy' => false,
            'is_tor' => false,
            'is_datacenter' => false,
            'is_abuser' => false,
            'abuse_score' => 0,
            'abuse_reports' => 0,
            'source' => $source,
        ];
    }

    public function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
