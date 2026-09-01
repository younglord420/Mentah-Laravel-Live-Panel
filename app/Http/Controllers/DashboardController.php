<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use App\Models\LoginLog;
use App\Support\TrafficLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function user(): View
    {
        return view('dashboard');
    }

    public function admin(Request $request): View
    {
        $filter = (string) $request->query('reason', 'all');

        return view('admin.dashboard', [
            'totalLogs' => LoginLog::query()->count(),
            'todayLogs' => LoginLog::query()
                ->whereDate('logged_in_at', now()->toDateString())
                ->count(),
            'activeSessions' => AccessSession::query()
                ->whereNotIn('status', [
                    AccessSession::STATUS_CLOSED,
                    AccessSession::STATUS_LOGOUT,
                    AccessSession::STATUS_LOGIN,
                ])
                ->where('email', '!=', '')
                ->count(),
            'recent' => LoginLog::query()
                ->orderByDesc('logged_in_at')
                ->limit(10)
                ->get(),
            'trafficStats' => TrafficLog::stats(),
            'trafficLogs' => TrafficLog::paginate(50, $filter),
            'filter' => $filter,
        ]);
    }

    public function clearTraffic(): RedirectResponse
    {
        TrafficLog::clearAll();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Semua traffic logs (visitor + block) dihapus.');
    }
}
