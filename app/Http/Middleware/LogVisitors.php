<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\VisitorLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVisitors
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldLog($request)) {
            VisitorLogger::log($request);
        }

        return $next($request);
    }

    protected function shouldLog(Request $request): bool
    {
        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->is('*/waiting/status')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->is('/') && Setting::isLoginEntryRequest($request)) {
            return false;
        }

        if ($request->is('/', 'logged-out', 's', 's/*')) {
            return true;
        }

        return false;
    }
}
