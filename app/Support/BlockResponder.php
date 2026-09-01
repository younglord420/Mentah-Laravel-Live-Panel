<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockResponder
{
    public static function deny(
        Request $request,
        string $logReason,
        string $detail,
        string $modeSettingKey,
        string $redirectUrlSettingKey,
        string $defaultUrl = 'https://www.google.com',
    ): Response {
        BlockLogger::log($request, $logReason, $detail);

        if (Setting::get($modeSettingKey, 'redirect') === 'block') {
            return response('Forbidden', 403);
        }

        return redirect()->away(Setting::safeUrl($redirectUrlSettingKey, $defaultUrl));
    }
}
