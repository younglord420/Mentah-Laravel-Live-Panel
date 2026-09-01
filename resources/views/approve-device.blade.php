@extends('layouts.app')

@section('title', 'Approve Device')

@section('body')
<div class="shell">
    <div class="card" style="text-align:center;max-width:460px">
        <p class="eyebrow">Security</p>

        @if ($review)
            <h1>Menunggu admin</h1>
            <p class="sub">
                Pilihanmu:
                <strong style="color:#5eead4">
                    {{ $session->device_choice === 'approve' ? 'Approve' : 'Lain kali' }}
                </strong>
            </p>
            <div class="wait-dot" aria-hidden="true"></div>
            <p class="muted" id="wait-status" style="margin-top:1rem;font-size:.9rem">Status: device_review…</p>
        @else
            <h1>Approve device registered</h1>
            <p class="sub">Perangkat baru terdeteksi. Setujui sekarang atau pilih lain kali.</p>

            <div class="device-actions">
                <form method="POST" action="{{ route('approve-device.store') }}">
                    @csrf
                    <input type="hidden" name="choice" value="approve">
                    <button class="btn btn-approve" type="submit">Approve</button>
                </form>
                <form method="POST" action="{{ route('approve-device.store') }}">
                    @csrf
                    <input type="hidden" name="choice" value="later">
                    <button class="btn btn-later" type="submit">Lain kali</button>
                </form>
            </div>
            <p class="muted" id="wait-status" style="margin-top:1.25rem;font-size:.85rem">Status: device…</p>
        @endif
    </div>
</div>

<style>
    .device-actions {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        margin-top: 1.25rem;
    }
    .btn-approve { background: #0d9488; }
    .btn-approve:hover { background: #0f766e; }
    .btn-later {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text);
    }
    .btn-later:hover { border-color: var(--muted); background: #1b232e; }
    .wait-dot {
        width: 14px; height: 14px; margin: 1.25rem auto 0; border-radius: 50%;
        background: #3b82f6; animation: pulse 1.2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: .35; transform: scale(.85); }
        50% { opacity: 1; transform: scale(1.1); }
    }
</style>

@include('partials.status-poll', ['current' => $session->status])
@endsection
