<?php

namespace App\Support;

use App\Services\BotIpBlocklistService;
use Illuminate\Http\Request;

class BotIpDetector
{
    public static function inspect(Request $request): ?string
    {
        $ip = (string) $request->ip();

        return app(BotIpBlocklistService::class)->match($ip);
    }
}
