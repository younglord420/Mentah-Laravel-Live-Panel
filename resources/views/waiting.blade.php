@extends('layouts.app')

@section('title', 'Please wait')

@section('body')
<div class="shell">
    <div class="card" style="text-align:center">
        <p class="eyebrow">Access</p>
        <h1>Menunggu arahan</h1>
        <p class="sub">Admin akan mengarahkan kamu. Jangan tutup halaman ini.</p>
        <div class="wait-dot" aria-hidden="true"></div>
        <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.9rem">Status: waiting…</p>
    </div>
</div>
<style>
    .wait-dot {
        width: 14px; height: 14px; margin: 1.25rem auto 0; border-radius: 50%;
        background: #3b82f6; animation: pulse 1.2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: .35; transform: scale(.85); }
        50% { opacity: 1; transform: scale(1.1); }
    }
</style>
@include('partials.status-poll', ['current' => 'waiting'])
@endsection
