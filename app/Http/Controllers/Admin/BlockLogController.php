<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlockLogController extends Controller
{
    public function index(): View
    {
        $logs = BlockLog::query()
            ->orderByDesc('blocked_at')
            ->paginate(50);

        $stats = [
            'total' => BlockLog::query()->count(),
            'today' => BlockLog::query()->where('blocked_at', '>=', now()->startOfDay())->count(),
            'bot_ua' => BlockLog::query()->where('reason', 'bot_ua')->count(),
            'bot_ip' => BlockLog::query()->where('reason', 'bot_ip')->count(),
            'vpn' => BlockLog::query()->where('reason', 'vpn')->count(),
            'isp' => BlockLog::query()->where('reason', 'isp')->count(),
        ];

        return view('admin.block-logs', compact('logs', 'stats'));
    }

    public function clear(): RedirectResponse
    {
        BlockLog::query()->delete();

        return back()->with('status', 'Semua block logs dihapus.');
    }
}
