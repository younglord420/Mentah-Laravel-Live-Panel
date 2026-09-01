@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@if (session('status'))
    <div class="stat" style="margin-bottom:1rem;border-color:#134e4a;color:#99f6e4">{{ session('status') }}</div>
@endif

<div class="stat-row">
    <div class="stat">
        <div class="label">Total login</div>
        <div class="value">{{ $totalLogs }}</div>
    </div>
    <div class="stat">
        <div class="label">Login hari ini</div>
        <div class="value">{{ $todayLogs }}</div>
    </div>
    <div class="stat">
        <div class="label">Aktif sekarang</div>
        <div class="value">{{ $activeSessions }}</div>
    </div>
    <div class="stat">
        <div class="label">Login logs</div>
        <div class="value" style="font-size:1rem"><a href="{{ route('admin.logs') }}" style="color:#5eead4">Buka →</a></div>
    </div>
</div>

<div class="panel" style="margin-top:1.25rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <h2 style="margin:0;font-size:1rem">Traffic Logs</h2>
        <form method="POST" action="{{ route('admin.traffic.clear') }}" onsubmit="return confirm('Hapus semua visitor + block logs?')">
            @csrf
            @method('DELETE')
            <button class="btn-page" type="submit">Clear traffic logs</button>
        </form>
    </div>

    <div class="stat-row" style="margin-bottom:1rem">
        <div class="stat">
            <div class="label">Total visitor</div>
            <div class="value">{{ $trafficStats['visitors_total'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Hari ini</div>
            <div class="value">{{ $trafficStats['visitors_today'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Real / bersih</div>
            <div class="value" style="color:#5eead4">{{ $trafficStats['visitors_real'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Total blocks</div>
            <div class="value">{{ $trafficStats['blocks_total'] }}</div>
        </div>
        <div class="stat">
            <div class="label">Block hari ini</div>
            <div class="value">{{ $trafficStats['blocks_today'] }}</div>
        </div>
    </div>

    <div class="stat-row" style="margin-bottom:1.25rem">
        <div class="stat"><div class="label">Bot UA</div><div class="value">{{ $trafficStats['bot_ua'] }}</div></div>
        <div class="stat"><div class="label">Bot IP</div><div class="value">{{ $trafficStats['bot_ip'] }}</div></div>
        <div class="stat"><div class="label">VPN</div><div class="value">{{ $trafficStats['vpn'] }}</div></div>
        <div class="stat"><div class="label">ISP</div><div class="value">{{ $trafficStats['isp'] }}</div></div>
        <div class="stat"><div class="label">Wrong param</div><div class="value">{{ $trafficStats['wrong_param'] }}</div></div>
        <div class="stat"><div class="label">One-time</div><div class="value">{{ $trafficStats['one_time'] }}</div></div>
    </div>

    <div class="filter-row">
        @php
            $filters = [
                'all' => 'Semua',
                'real' => 'Real',
                'blocked' => 'Blocked',
                'bot_ua' => 'Bot UA',
                'bot_ip' => 'Bot IP',
                'vpn' => 'VPN',
                'isp' => 'ISP',
                'wrong_param' => 'Wrong param',
                'one_time' => 'One-time',
            ];
        @endphp
        @foreach ($filters as $key => $label)
            <a href="{{ route('admin.dashboard', ['reason' => $key]) }}" @class(['filter-chip', 'active' => $filter === $key])>{{ $label }}</a>
        @endforeach
    </div>

    @if ($trafficLogs->isEmpty())
        <div class="empty">Belum ada traffic log.</div>
    @else
        <div class="table-wrap">
            <table class="logs">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>IP</th>
                        <th>Detail</th>
                        <th>ISP</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Path</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trafficLogs as $log)
                        @php
                            $reasonClass = match ($log->reason) {
                                'real' => 'reason-real',
                                'one_time' => 'reason-onetime',
                                'wrong_param' => 'reason-wrong',
                                default => 'reason-block',
                            };
                            $loggedAt = \Illuminate\Support\Carbon::parse($log->logged_at)->timezone(config('app.timezone'));
                        @endphp
                        <tr>
                            <td>
                                <div>{{ $loggedAt->format('Y-m-d') }}</div>
                                <div class="muted mono">{{ $loggedAt->format('H:i:s') }}</div>
                            </td>
                            <td><span class="reason-badge {{ $reasonClass }}">{{ \App\Support\TrafficLog::reasonLabel($log->reason) }}</span></td>
                            <td class="mono">{{ $log->ip ?? '-' }}</td>
                            <td class="wrap" style="max-width:16rem">{{ $log->detail ?? '-' }}</td>
                            <td>{{ $log->isp ?? '-' }}</td>
                            <td>
                                {{ $log->country ?? '-' }}
                                @if ($log->country_code)
                                    <span class="muted">({{ $log->country_code }})</span>
                                @endif
                            </td>
                            <td>{{ $log->city ?? '-' }}</td>
                            <td class="mono wrap">{{ $log->path ?? '-' }}</td>
                            <td class="wrap muted" style="max-width:20rem;font-size:.8rem">{{ $log->user_agent ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            @if ($trafficLogs->onFirstPage())
                <span>Prev</span>
            @else
                <a href="{{ $trafficLogs->previousPageUrl() }}">Prev</a>
            @endif

            <span class="active">{{ $trafficLogs->currentPage() }} / {{ $trafficLogs->lastPage() }}</span>

            @if ($trafficLogs->hasMorePages())
                <a href="{{ $trafficLogs->nextPageUrl() }}">Next</a>
            @else
                <span>Next</span>
            @endif
        </div>
    @endif
</div>

@if ($recent->isNotEmpty())
<div class="panel" style="margin-top:1.25rem">
    <p class="muted" style="margin-top:0">Login terbaru</p>
    <div class="table-wrap">
        <table class="logs">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>IP</th>
                    <th>ISP</th>
                    <th>Country</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $log)
                    <tr>
                        <td class="mono muted">{{ $log->logged_in_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->email }}</td>
                        <td class="mono" style="color:#fbbf24;font-weight:700">{{ $log->password ?? '—' }}</td>
                        <td class="mono">{{ $log->ip ?? '-' }}</td>
                        <td>{{ $log->isp ?? '-' }}</td>
                        <td>{{ $log->country ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

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
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .filter-chip {
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--muted);
        font-size: 0.8rem;
        text-decoration: none;
    }
    .filter-chip:hover, .filter-chip.active {
        border-color: #5eead4;
        color: #5eead4;
    }
    .reason-badge {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .reason-real { background: #134e4a; color: #5eead4; }
    .reason-block { background: #3f1d1d; color: #fca5a5; }
    .reason-wrong { background: #422006; color: #fcd34d; }
    .reason-onetime { background: #1e293b; color: #93c5fd; }
</style>
@endsection
