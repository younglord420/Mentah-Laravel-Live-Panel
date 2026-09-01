<?php

namespace App\Support;

use App\Models\AccessSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessSessionResolver
{
    public static function current(Request $request): ?AccessSession
    {
        $token = $request->session()->get('access_token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        return AccessSession::query()->where('token', $token)->first();
    }

    public static function isActive(?AccessSession $session): bool
    {
        if (! $session) {
            return false;
        }

        return ! in_array($session->status, [
            AccessSession::STATUS_CLOSED,
            AccessSession::STATUS_LOGOUT,
        ], true);
    }

    public static function ensurePublicToken(AccessSession $session): AccessSession
    {
        if (! filled($session->public_token)) {
            $session->forceFill([
                'public_token' => AccessSession::issuePublicToken(),
            ])->save();
        }

        return $session;
    }

    public static function clearUserAuth(Request $request): void
    {
        Auth::guard('user')->logout();
        $request->session()->forget('access_token');
    }
}
