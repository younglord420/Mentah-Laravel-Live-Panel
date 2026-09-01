<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramDiagnose extends Command
{
    protected $signature = 'telegram:diagnose {--send : Kirim pesan test ke chat ID tersimpan}';

    protected $description = 'Diagnosa koneksi Telegram bot (token, chat ID, chats dikenal)';

    public function handle(TelegramService $telegram): int
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $chatId = trim((string) Setting::get('telegram_chat_id', ''));

        if ($token === '') {
            $this->error('Bot token kosong di settings.');

            return self::FAILURE;
        }

        $verify = $telegram->verifyToken();
        if ($verify['ok']) {
            $this->info('Token OK — bot @'.($verify['username'] ?? '?'));
        } else {
            $this->error('Token gagal: '.($verify['error'] ?? 'unknown'));

            return self::FAILURE;
        }

        $this->line('Chat ID tersimpan: '.($chatId ?: '(kosong)'));

        if ($chatId !== '') {
            $chatInfo = $this->getChat($token, $chatId);
            if ($chatInfo) {
                $this->warn('Chat ID ini milik: '.json_encode($chatInfo, JSON_UNESCAPED_UNICODE));
                if (($chatInfo['type'] ?? '') === 'private' && ($chatInfo['username'] ?? '') === ($verify['username'] ?? null)) {
                    $this->error('↑ Ini ID bot sendiri — tidak bisa dipakai. Pakai ID channel (-100...) atau chat user.');
                }
            } else {
                $this->error('Chat ID tidak ditemukan di Telegram (chat not found).');
            }
        }

        $chats = $this->knownChats($token);
        if ($chats === []) {
            $this->warn('Tidak ada chat di getUpdates. Pastikan bot sudah jadi admin channel & posting di channel, atau kirim /start ke bot.');
            $this->warn('Jika webhook aktif, getUpdates kosong — normal.');
        } else {
            $this->info('Chat yang pernah terlihat bot:');
            foreach ($chats as $id => $meta) {
                $this->line("  {$id}  [{$meta['type']}]  ".($meta['title'] ?? '-'));
            }
        }

        if ($this->option('send') && $chatId !== '') {
            $result = $telegram->sendMessageDetailed('✅ Test diagnose dari server');
            if ($result['ok']) {
                $this->info('Kirim pesan: OK');
            } else {
                $this->error('Kirim pesan gagal: '.($result['error'] ?? 'unknown'));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{type:string,title:?string,username:?string}>|null
     */
    protected function knownChats(string $token): array
    {
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
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getChat(string $token, string $chatId): ?array
    {
        $res = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getChat", ['chat_id' => $chatId]);
        if ($res->successful() && ($res->json('ok') ?? false)) {
            return $res->json('result');
        }

        return null;
    }
}
