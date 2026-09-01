@extends('layouts.admin')

@section('title', 'Block Logs')
@section('heading', 'Block Logs')

@section('content')
@if (session('status'))
    <div class="stat" style="margin-bottom:1rem;border-color:#134e4a;color:#99f6e4">{{ session('status') }}</div>
@endif

<div class="stat-row">
    <div class="stat">
        <div class="label">Total blocks</div>
        <div class="value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Hari ini</div>
        <div class="value">{{ $stats['today'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Bot UA</div>
        <div class="value">{{ $stats['bot_ua'] }}</div>
    </div>
    <div class="stat">
        <div class="label">Bot IP</div>
        <div class="value">{{ $stats['bot_ip'] }}</div>
    </div>
    <div class="stat">
        <div class="label">VPN</div>
        <div class="value">{{ $stats['vpn'] }}</div>
    </div>
    <div class="stat">
        <div class="label">ISP</div>
        <div class="value">{{ $stats['isp'] }}</div>
    </div>
</div>

<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <p class="muted" style="margin:0">
            Log setiap request yang diblokir anti-bot, bot IP, anti-VPN, atau block ISP — beserta alasan detailnya.
        </p>
        <form method="POST" action="{{ route('admin.block-logs.clear') }}" onsubmit="return confirm('Hapus semua block logs?')">
            @csrf
            @method('DELETE')
            <button class="btn-page" type="submit">Clear all</button>
        </form>
    </div>

    @if ($logs->isEmpty())
        <div class="empty">Belum ada block log.</div>
    @else
        <div class="table-wrap">
            <table class="logs">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>IP</th>
                        <th>Detail</th>
                        <th>Path</th>
                        <th>Method</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="mono muted">{{ $log->id }}</td>
                            <td>
                                <div>{{ $log->blocked_at->timezone(config('app.timezone'))->format('Y-m-d') }}</div>
                                <div class="muted mono">{{ $log->blocked_at->timezone(config('app.timezone'))->format('H:i:s') }}</div>
                            </td>
                            <td>{{ \App\Models\BlockLog::reasonLabel($log->reason) }}</td>
                            <td class="mono">{{ $log->ip ?? '-' }}</td>
                            <td class="wrap" style="max-width:18rem">{{ $log->detail ?? '-' }}</td>
                            <td class="mono wrap">{{ $log->path ?? '-' }}</td>
                            <td class="mono">{{ $log->method ?? '-' }}</td>
                            <td class="wrap muted" style="max-width:22rem;font-size:.8rem">{{ $log->user_agent ?? '-' }}</td>
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
