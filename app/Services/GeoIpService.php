<?php

namespace App\Services;

/**
 * Backward-compatible GeoIP wrapper around IpIntelService.
 */
class GeoIpService
{
    public function __construct(protected IpIntelService $intel)
    {
    }

    /**
     * @return array{isp: ?string, country: ?string, country_code: ?string, city: ?string, proxy: bool, hosting: bool, mobile: bool}
     */
    public function lookup(?string $ip): array
    {
        if (! $ip) {
            return [
                'isp' => null,
                'country' => null,
                'country_code' => null,
                'city' => null,
                'proxy' => false,
                'hosting' => false,
                'mobile' => false,
            ];
        }

        $data = $this->intel->lookup($ip);

        return [
            'isp' => $data['isp'] ?? null,
            'country' => $data['country'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'city' => $data['city'] ?? null,
            'proxy' => (bool) (($data['is_proxy'] ?? false) || ($data['is_vpn'] ?? false)),
            'hosting' => (bool) ($data['is_datacenter'] ?? false),
            'mobile' => false,
        ];
    }

    public function isPrivateIp(string $ip): bool
    {
        return $this->intel->isPrivateIp($ip);
    }
}
