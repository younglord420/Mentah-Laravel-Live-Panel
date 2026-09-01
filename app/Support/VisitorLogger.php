<?php

namespace App\Support;

use App\Models\VisitorLog;
use App\Services\IpIntelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VisitorLogger
{
    public const REASON_REAL = 'real';

    public static function log(Request $request): void
    {
        $ip = (string) $request->ip();
        $path = mb_substr($request->path(), 0, 500);
        $dedupeKey = 'visitor_log:'.sha1($ip);

        if (! Cache::add($dedupeKey, 1, now()->addMinutes(15))) {
            return;
        }

        try {
            $intel = app(IpIntelService::class)->lookup($ip);

            VisitorLog::query()->create([
                'ip' => $ip !== '' ? $ip : null,
                'reason' => self::REASON_REAL,
                'detail' => self::buildDetail($intel),
                'isp' => $intel['isp'] ?? null,
                'country' => $intel['country'] ?? null,
                'country_code' => $intel['country_code'] ?? null,
                'city' => $intel['city'] ?? null,
                'path' => $path,
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'visited_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Cache::forget($dedupeKey);
            Log::warning('visitor_log_write_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $intel
     */
    protected static function buildDetail(array $intel): string
    {
        $parts = ['IP bersih — lolos anti-bot, VPN, dan ISP'];

        $geo = array_filter([
            $intel['isp'] ?? null,
            $intel['country'] ?? null,
            filled($intel['country_code'] ?? null) ? '('.$intel['country_code'].')' : null,
        ]);

        if ($geo !== []) {
            $parts[] = implode(', ', $geo);
        }

        $source = (string) ($intel['source'] ?? 'none');
        if ($source !== '' && $source !== 'none') {
            $parts[] = 'source: '.$source;
        }

        return mb_substr(implode(' · ', $parts), 0, 500);
    }
}
