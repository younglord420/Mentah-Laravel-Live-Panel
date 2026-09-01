<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginLogController extends Controller
{
    public function index(): View
    {
        $logs = LoginLog::query()
            ->orderByDesc('logged_in_at')
            ->paginate(50);

        return view('admin.logs', compact('logs'));
    }

    public function clear(): RedirectResponse
    {
        LoginLog::query()->delete();

        return back()->with('status', 'Semua login logs dihapus.');
    }
}
