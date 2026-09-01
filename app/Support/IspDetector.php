<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\IpIntelService;
use Illuminate\Http\Request;

class IspDetector
{
    /**
     * Default bad / bot / hosting / VPN ISP & ASN list.
     *
     * @var list<string>
     */
    public const DEFAULT_BLOCKLIST = [
        // VPN brands
        'nordvpn',
        'expressvpn',
        'surfshark',
        'mullvad',
        'proton ag',
        'protonvpn',
        'private internet access',
        'cyberghost',
        'ipvanish',
        'hotspot shield',
        'tunnelbear',
        'windscribe',
        'purevpn',
        'vyprvpn',
        'hide.me',
        'torguard',
        'pia vpn',
        // Hosting / datacenter (sering bot & VPN exit)
        'digitalocean',
        'linode',
        'akamai technologies',
        'vultr',
        'ovh',
        'hetzner',
        'contabo',
        'leaseweb',
        'choopa',
        'm247',
        'datacamp',
        'psychz',
        'quadranet',
        'hostinger',
        'hostgator',
        'bluehost',
        'godaddy',
        'namecheap',
        'amazon.com',
        'amazon technologies',
        'amazon data services',
        'google cloud',
        'google llc',
        'microsoft corporation',
        'microsoft azure',
        'alibaba',
        'tencent cloud',
        'oracle cloud',
        'cloudflare',
        'fastly',
        'ovh sas',
        'online sas',
        'scaleway',
        'hivelocity',
        'colocrossing',
        'serverius',
        'worldstream',
        'flokinet',
        'buyvm',
        'frantech',
        'ramnode',
        'forknetworking',
        // Bot / scanner / abuse networks (nama umum)
        'zmap',
        'censys',
        'shodan',
        'binaryedge',
        'shadowserver',
        'netcraft',
        // ASN yang sering dipakai cloud/VPN (bisa dihapus di settings jika perlu)
        'AS14061', // DigitalOcean
        'AS63949', // Linode
        'AS20473', // Choopa/Vultr
        'AS16276', // OVH
        'AS24940', // Hetzner
        'AS16509', // Amazon
        'AS14618', // Amazon
        'AS15169', // Google
        'AS8075',  // Microsoft
        'AS13335', // Cloudflare
        'AS9009',  // M247
        'AS60068', // Datacamp / CDN77
        'AS62240', // Clouvider
        'AS51167', // Contabo
    ];

    public static function isBlocked(Request $request): bool
    {
        return self::inspect($request) !== null;
    }

    public static function inspect(Request $request): ?string
    {
        $ip = (string) $request->ip();
        $intel = app(IpIntelService::class);

        if ($ip === '' || $intel->isPrivateIp($ip)) {
            return null;
        }

        $data = $intel->lookup($ip);

        if (Setting::bool('abuseipdb_enabled') && ($data['is_abuser'] ?? false)) {
            $reports = (int) ($data['abuse_reports'] ?? 0);

            return 'AbuseIPDB: totalReports='.$reports.' (>0)';
        }

        $haystack = strtolower(trim(implode(' ', array_filter([
            $data['isp'] ?? '',
            $data['org'] ?? '',
            $data['asn'] ?? '',
        ]))));

        if ($haystack === '') {
            return null;
        }

        foreach (self::blocklist() as $entry) {
            if ($entry === '') {
                continue;
            }
            if (str_contains($haystack, $entry)) {
                return 'ISP blocklist matched: '.$entry;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function blocklist(): array
    {
        $custom = (string) Setting::get('block_isp_list', '');
        if (trim($custom) === '') {
            $list = self::DEFAULT_BLOCKLIST;
        } else {
            $list = [];
            foreach (preg_split('/\r\n|\r|\n/', $custom) ?: [] as $line) {
                $line = strtolower(trim($line));
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $list[] = $line;
            }
        }

        return array_values(array_unique($list));
    }

    public static function defaultListText(): string
    {
        return implode("\n", self::DEFAULT_BLOCKLIST);
    }
}
