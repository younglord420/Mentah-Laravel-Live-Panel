<?php

namespace App\Support;

use App\Models\BlockLog;
use App\Services\IpIntelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlockLogger
{
    public const REASON_BOT_UA = 'bot_ua';

    public const REASON_BOT_IP = 'bot_ip';

    public const REASON_VPN = 'vpn';

    public const REASON_ISP = 'isp';

    public const REASON_WRONG_PARAM = 'wrong_param';

    public const REASON_ONE_TIME = 'one_time';

    public static function log(Request $request, string $reason, string $detail): void
    {
        try {
            $ip = (string) $request->ip();
            $intel = $ip !== '' ? app(IpIntelService::class)->lookup($ip) : [];

            BlockLog::query()->create([
                'ip' => $ip !== '' ? $ip : null,
                'reason' => $reason,
                'detail' => mb_substr($detail, 0, 500),
                'isp' => $intel['isp'] ?? null,
                'country' => $intel['country'] ?? null,
                'country_code' => $intel['country_code'] ?? null,
                'city' => $intel['city'] ?? null,
                'path' => mb_substr($request->path(), 0, 500),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'blocked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('block_log_write_failed', ['error' => $e->getMessage()]);
        }
    }
}
