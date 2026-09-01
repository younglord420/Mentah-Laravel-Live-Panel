<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use App\Support\BotDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBots
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::bool('anti_bot_enabled')) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'telegram/webhook/*', 'up')) {
            return $next($request);
        }

        $detail = BotDetector::inspect($request);
        if ($detail === null) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_BOT_UA,
            $detail,
            'anti_bot_mode',
            'anti_bot_redirect_url',
        );
    }
}
