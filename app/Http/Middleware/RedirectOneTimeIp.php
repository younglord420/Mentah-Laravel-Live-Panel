<?php

namespace App\Http\Middleware;

use App\Models\OneTimeIp;
use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectOneTimeIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return $next($request);
        }

        if (! Setting::oneTimeEnabled() || ! OneTimeIp::hasIp($request->ip())) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_ONE_TIME,
            'IP sudah pernah selesai — redirect one-time',
            'one_time_mode',
            'one_time_redirect_url',
        );
    }
}
