<?php

namespace App\Support;

use App\Models\BlockLog;
use App\Models\VisitorLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TrafficLog
{
    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            VisitorLogger::REASON_REAL => 'Real',
            BlockLogger::REASON_BOT_UA => 'Bot UA',
            BlockLogger::REASON_BOT_IP => 'Bot IP',
            BlockLogger::REASON_VPN => 'VPN',
            BlockLogger::REASON_ISP => 'ISP',
            BlockLogger::REASON_WRONG_PARAM => 'Wrong Parameter',
            BlockLogger::REASON_ONE_TIME => 'One-time',
            default => $reason,
        };
    }

    /**
     * @return array<string, int>
     */
    public static function stats(): array
    {
        $today = now()->startOfDay();

        return [
            'visitors_total' => VisitorLog::query()->count(),
            'visitors_today' => VisitorLog::query()->where('visited_at', '>=', $today)->count(),
            'visitors_real' => VisitorLog::query()->where('reason', VisitorLogger::REASON_REAL)->count(),
            'blocks_total' => BlockLog::query()->count(),
            'blocks_today' => BlockLog::query()->where('blocked_at', '>=', $today)->count(),
            'bot_ua' => BlockLog::query()->where('reason', BlockLogger::REASON_BOT_UA)->count(),
            'bot_ip' => BlockLog::query()->where('reason', BlockLogger::REASON_BOT_IP)->count(),
            'vpn' => BlockLog::query()->where('reason', BlockLogger::REASON_VPN)->count(),
            'isp' => BlockLog::query()->where('reason', BlockLogger::REASON_ISP)->count(),
            'wrong_param' => BlockLog::query()->where('reason', BlockLogger::REASON_WRONG_PARAM)->count(),
            'one_time' => BlockLog::query()->where('reason', BlockLogger::REASON_ONE_TIME)->count(),
        ];
    }

    public static function paginate(int $perPage = 50, ?string $filter = null): LengthAwarePaginator
    {
        $visitors = DB::table('visitor_logs')
            ->select([
                'id',
                DB::raw("'visitor' as log_type"),
                'ip',
                'reason',
                'detail',
                'isp',
                'country',
                'country_code',
                'city',
                'path',
                'user_agent',
                DB::raw('visited_at as logged_at'),
            ]);

        $blocks = DB::table('block_logs')
            ->select([
                'id',
                DB::raw("'block' as log_type"),
                'ip',
                'reason',
                'detail',
                'isp',
                'country',
                'country_code',
                'city',
                'path',
                'user_agent',
                DB::raw('blocked_at as logged_at'),
            ]);

        if ($filter === VisitorLogger::REASON_REAL) {
            $union = $visitors;
        } elseif ($filter === 'blocked') {
            $union = $blocks;
        } elseif ($filter !== null && $filter !== '' && $filter !== 'all') {
            $union = DB::table('block_logs')
                ->select([
                    'id',
                    DB::raw("'block' as log_type"),
                    'ip',
                    'reason',
                    'detail',
                    'isp',
                    'country',
                    'country_code',
                    'city',
                    'path',
                    'user_agent',
                    DB::raw('blocked_at as logged_at'),
                ])
                ->where('reason', $filter);
        } else {
            $union = $visitors->unionAll($blocks);
        }

        return DB::query()
            ->fromSub($union, 'traffic')
            ->orderByDesc('logged_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public static function clearAll(): void
    {
        VisitorLog::query()->delete();
        BlockLog::query()->delete();
    }
}
