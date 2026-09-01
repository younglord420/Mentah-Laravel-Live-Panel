<?php

namespace App\Support;

use App\Models\Setting;

class ManualIpBlacklist
{
    public const SETTING_KEY = 'block_bot_ip_extra';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $raw = (string) Setting::get(self::SETTING_KEY, '');
        $ips = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (filter_var($line, FILTER_VALIDATE_IP)) {
                $ips[] = $line;
            }
        }

        return array_values(array_unique($ips));
    }

    public static function has(string $ip): bool
    {
        return in_array($ip, self::all(), true);
    }

    public static function add(string $ip): bool
    {
        $ip = trim($ip);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ips = self::all();
        if (in_array($ip, $ips, true)) {
            return true;
        }

        $ips[] = $ip;
        self::save($ips);

        return true;
    }

    public static function remove(string $ip): bool
    {
        $ip = trim($ip);
        $ips = array_values(array_filter(
            self::all(),
            fn (string $item): bool => $item !== $ip
        ));

        if (count($ips) === count(self::all())) {
            return false;
        }

        self::save($ips);

        return true;
    }

    /**
     * @param  list<string>  $ips
     */
    protected static function save(array $ips): void
    {
        Setting::set(self::SETTING_KEY, implode("\n", array_values(array_unique($ips))));
    }
}
