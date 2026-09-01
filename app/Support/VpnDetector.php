<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\IpIntelService;
use Illuminate\Http\Request;

class VpnDetector
{
    public static function isVpnOrProxy(Request $request): bool
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

        $blockProxy = Setting::bool('anti_vpn_block_proxy', true);
        $blockHosting = Setting::bool('anti_vpn_block_hosting', true);

        if ($blockProxy && (($data['is_vpn'] ?? false) || ($data['is_proxy'] ?? false) || ($data['is_tor'] ?? false))) {
            $flags = array_filter([
                ($data['is_vpn'] ?? false) ? 'vpn' : null,
                ($data['is_proxy'] ?? false) ? 'proxy' : null,
                ($data['is_tor'] ?? false) ? 'tor' : null,
            ]);

            return 'VPN/proxy detected ('.implode(', ', $flags).', source: '.($data['source'] ?? 'unknown').')';
        }

        if ($blockHosting && ($data['is_datacenter'] ?? false)) {
            return 'Datacenter/hosting IP (source: '.($data['source'] ?? 'unknown').')';
        }

        $isp = strtolower(trim(implode(' ', array_filter([
            $data['isp'] ?? '',
            $data['org'] ?? '',
        ]))));

        foreach (self::ispKeywords() as $keyword) {
            if ($keyword !== '' && str_contains($isp, $keyword)) {
                return 'ISP keyword matched: '.$keyword;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function ispKeywords(): array
    {
        $extra = (string) Setting::get('anti_vpn_extra_isp', '');
        $custom = [];
        foreach (preg_split('/\r\n|\r|\n/', $extra) ?: [] as $line) {
            $line = strtolower(trim($line));
            if ($line !== '' && ! str_starts_with($line, '#')) {
                $custom[] = $line;
            }
        }

        return $custom;
    }
}
