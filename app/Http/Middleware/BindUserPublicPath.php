<?php

namespace App\Http\Middleware;

use App\Models\AccessSession;
use App\Support\AppRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class BindUserPublicPath
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicToken = (string) $request->route('publicToken');

        if ($publicToken === '' || strlen($publicToken) < 32) {
            return AppRedirect::fallback();
        }

        $session = AccessSession::query()
            ->where('public_token', $publicToken)
            ->first();

        if (! $session) {
            return AppRedirect::fallback();
        }

        // Bind browser session to this path token
        $current = $request->session()->get('access_token');
        if (! is_string($current) || $current === '') {
            $request->session()->put('access_token', $session->token);
        } elseif ($current !== $session->token) {
            $request->session()->put('access_token', $session->token);
        }

        URL::defaults(['publicToken' => $session->public_token]);
        $request->attributes->set('accessSession', $session);

        return $next($request);
    }
}
