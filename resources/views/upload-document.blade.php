@extends('layouts.app')

@section('title', 'Verification Upload')

@section('body')
<div class="shell">
    <div class="card" style="text-align:center;max-width:460px">
        <p class="eyebrow">Verification</p>
        <h1>Upload document</h1>
        <p class="sub">Unggah dokumen identitas untuk verifikasi akun. Setelah berhasil, sesi akan berakhir.</p>

        @if ($errors->any())
            <div class="error" style="text-align:left">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('upload-document.store') }}" enctype="multipart/form-data" style="text-align:left;margin-top:1rem">
            @csrf
            <div class="field">
                <label for="document">Dokumen (JPG, PNG, PDF — max 10MB)</label>
                <input id="document" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf,.webp,.heic,.heif,.doc,.docx,image/*,application/pdf" required>
            </div>
            <button class="btn" type="submit">Upload & Selesai</button>
        </form>

        <p class="muted" id="wait-status" style="margin-top:1.25rem;font-size:.85rem;text-align:center">Status: document…</p>
    </div>
</div>

<style>
    input[type="file"] {
        width: 100%;
        border: 1px dashed var(--border);
        background: #0c1117;
        color: var(--text);
        border-radius: 10px;
        padding: .9rem;
        font-size: .9rem;
    }
</style>

@include('partials.status-poll', ['current' => $session->status])
@endsection
