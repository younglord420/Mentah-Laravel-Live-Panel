<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #0f1419;
            --panel: #171d25;
            --sidebar: #121820;
            --border: #2a3441;
            --text: #e8eef5;
            --muted: #8b98a8;
            --accent: #0d9488;
            --accent-soft: #134e4a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .admin-shell {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.25rem 0.85rem;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .brand {
            padding: 0.35rem 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .brand .name {
            font-weight: 700;
            font-size: 1.05rem;
        }
        .brand .meta {
            margin-top: 0.35rem;
            color: var(--muted);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .badge-admin {
            display: inline-block;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.7rem;
            background: var(--accent-soft);
            color: #5eead4;
        }
        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex: 1;
        }
        .side-nav a {
            display: block;
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            color: var(--muted);
            font-size: 0.95rem;
        }
        .side-nav a:hover {
            background: #1b232e;
            color: var(--text);
        }
        .side-nav a.active {
            background: color-mix(in srgb, var(--accent) 22%, transparent);
            color: #99f6e4;
            font-weight: 600;
        }
        .side-foot {
            border-top: 1px solid var(--border);
            padding-top: 0.85rem;
            margin-top: 0.85rem;
        }
        .side-foot form { margin: 0; }
        .btn-ghost {
            width: 100%;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.55rem 0.9rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .btn-ghost:hover { border-color: var(--muted); }
        .admin-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .admin-top {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: color-mix(in srgb, var(--panel) 88%, transparent);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .admin-top h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 650;
        }
        .admin-content {
            padding: 1.25rem 1.5rem 2rem;
            width: 100%;
            max-width: none;
            flex: 1;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem 1.35rem;
            width: 100%;
        }
        .muted { color: var(--muted); }
        .table-wrap {
            overflow-x: auto;
            width: 100%;
            margin: 0 -0.15rem;
        }
        table.logs {
            width: 100%;
            min-width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            table-layout: auto;
        }
        table.logs th, table.logs td {
            text-align: left;
            padding: 0.85rem 0.85rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            white-space: nowrap;
        }
        table.logs td.wrap {
            white-space: normal;
            word-break: break-word;
            min-width: 12rem;
        }
        table.logs th {
            color: var(--muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        table.logs tr:hover td { background: #1b232e; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85rem; }
        .empty { padding: 2rem; text-align: center; color: var(--muted); }
        .pager { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; flex-wrap: wrap; }
        .pager a, .pager span {
            padding: 0.4rem 0.7rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            font-size: 0.85rem;
        }
        .pager a:hover { color: var(--text); }
        .pager .active { color: var(--text); border-color: var(--accent); }
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .stat {
            background: #121820;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem 1rem;
        }
        .stat .label { color: var(--muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .stat .value { font-size: 1.4rem; font-weight: 650; margin-top: 0.25rem; }
        @media (max-width: 800px) {
            .admin-shell { grid-template-columns: 1fr; }
            .sidebar {
                position: static;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }
            .side-nav { flex-direction: row; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
@php
    $admin = auth('web')->user();
@endphp
<div class="admin-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="name">Admin Panel</div>
            <div class="meta">
                <span>{{ $admin->name }}</span>
                <span class="badge-admin">admin</span>
            </div>
        </div>

        <nav class="side-nav">
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
            <a href="{{ route('admin.logs') }}" @class(['active' => request()->routeIs('admin.logs')])>Login Logs</a>
            <a href="{{ route('admin.access') }}" @class(['active' => request()->routeIs('admin.access')])>Access</a>
            <a href="{{ route('admin.settings') }}" @class(['active' => request()->routeIs('admin.settings*')])>Settings</a>
        </nav>

        <div class="side-foot">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn-ghost" type="submit">Logout</button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-top">
            <h1>@yield('heading', 'Admin')</h1>
        </header>
        <main class="admin-content">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
