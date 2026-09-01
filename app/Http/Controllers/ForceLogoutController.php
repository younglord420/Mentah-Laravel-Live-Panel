<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ForceLogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $token = $request->session()->get('access_token');

        if (is_string($token) && $token !== '') {
            AccessSession::query()
                ->where('token', $token)
                ->update(['status' => AccessSession::STATUS_CLOSED]);
        }

        Auth::guard('user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('logged-out');
    }

    public function loggedOut(): View
    {
        return view('logged-out');
    }
}
