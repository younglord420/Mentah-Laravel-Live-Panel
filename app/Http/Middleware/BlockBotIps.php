<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use App\Support\BotIpDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBotIps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('block_bot_ip_enabled')) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return $next($request);
        }

        try {
            $detail = BotIpDetector::inspect($request);
            if ($detail === null) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_BOT_IP,
            $detail,
            'block_bot_ip_mode',
            'block_bot_ip_redirect_url',
        );
    }
}
