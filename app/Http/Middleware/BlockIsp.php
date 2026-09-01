<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use App\Support\IspDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockIsp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('block_isp_enabled')) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return $next($request);
        }

        try {
            $detail = IspDetector::inspect($request);
            if ($detail === null) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_ISP,
            $detail,
            'block_isp_mode',
            'block_isp_redirect_url',
        );
    }
}
