<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorLog;
use App\Support\VisitorLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitorLogController extends Controller
{
    public function index(): View
    {
        $logs = VisitorLog::query()
            ->orderByDesc('visited_at')
            ->paginate(50);

        return view('admin.visitor-logs', [
            'logs' => $logs,
            'stats' => [
                'total' => VisitorLog::query()->count(),
                'today' => VisitorLog::query()->where('visited_at', '>=', now()->startOfDay())->count(),
                'real' => VisitorLog::query()->where('reason', VisitorLogger::REASON_REAL)->count(),
            ],
        ]);
    }

    public function clear(): RedirectResponse
    {
        VisitorLog::query()->delete();

        return redirect()
            ->route('admin.visitor-logs')
            ->with('status', 'Semua visitor logs dihapus.');
    }
}
