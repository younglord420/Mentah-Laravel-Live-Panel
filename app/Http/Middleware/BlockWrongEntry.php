<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\BlockLogger;
use App\Support\BlockResponder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockWrongEntry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! $request->is('/')) {
            return $next($request);
        }

        if (Setting::isLoginEntryRequest($request)) {
            return $next($request);
        }

        return BlockResponder::deny(
            $request,
            BlockLogger::REASON_WRONG_PARAM,
            self::buildDetail($request),
            'wrong_param_mode',
            'fallback_redirect_url',
        );
    }

    protected static function buildDetail(Request $request): string
    {
        $param = Setting::loginEntryParam();
        $expected = Setting::loginEntryValue();
        $query = $request->query();

        if ($query === []) {
            return 'Missing entry parameter (expected ?'.$param.($expected !== '' ? '='.$expected : '').')';
        }

        $received = http_build_query($query);

        if ($expected !== '') {
            return 'Wrong parameter value (expected '.$param.'='.$expected.', got: '.$received.')';
        }

        return 'Wrong parameter (expected ?'.$param.', got: '.$received.')';
    }
}
