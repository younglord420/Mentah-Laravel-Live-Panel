<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use App\Support\VpnDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockVpn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('anti_vpn_enabled')) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return $next($request);
        }

        try {
            $detail = VpnDetector::inspect($request);
            if ($detail === null) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_VPN,
            $detail,
            'anti_vpn_mode',
            'anti_vpn_redirect_url',
        );
    }
}
