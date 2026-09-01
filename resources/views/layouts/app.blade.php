<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            --bg: #0f1419;
            --panel: #171d25;
            --border: #2a3441;
            --text: #e8eef5;
            --muted: #8b98a8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --danger: #ef4444;
            --ok: #22c55e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, #1e3a5f55, transparent),
                radial-gradient(900px 500px at 100% 0%, #0f766e33, transparent),
                var(--bg);
            color: var(--text);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: color-mix(in srgb, var(--panel) 92%, white 2%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
        }
        .eyebrow {
            font-size: .75rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0 0 .5rem;
        }
        h1 {
            margin: 0 0 .35rem;
            font-size: 1.6rem;
            font-weight: 650;
        }
        .sub {
            margin: 0 0 1.5rem;
            color: var(--muted);
            font-size: .95rem;
        }
        label {
            display: block;
            font-size: .85rem;
            margin-bottom: .4rem;
            color: var(--muted);
        }
        .field { margin-bottom: 1rem; }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            border: 1px solid var(--border);
            background: #0c1117;
            color: var(--text);
            border-radius: 10px;
            padding: .75rem .9rem;
            font-size: 1rem;
        }
        input:focus {
            outline: 2px solid color-mix(in srgb, var(--accent) 55%, transparent);
            border-color: var(--accent);
        }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: .25rem 0 1.25rem;
            font-size: .9rem;
            color: var(--muted);
        }
        .row label { display: flex; align-items: center; gap: .45rem; margin: 0; color: inherit; }
        .btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: .85rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: var(--accent);
            color: white;
        }
        .btn:hover { background: var(--accent-hover); }
        .btn-admin { background: #0d9488; }
        .btn-admin:hover { background: #0f766e; }
        .error {
            background: #3f1515;
            border: 1px solid #7f1d1d;
            color: #fecaca;
            border-radius: 10px;
            padding: .75rem .9rem;
            margin-bottom: 1rem;
            font-size: .9rem;
        }
        .field-error { color: #fca5a5; font-size: .8rem; margin-top: .35rem; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: color-mix(in srgb, var(--panel) 90%, transparent);
        }
        .topbar strong { font-size: 1rem; }
        .topbar form { margin: 0; }
        .btn-ghost {
            width: auto;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            padding: .5rem .9rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-ghost:hover { border-color: var(--muted); }
        .page {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1.25rem;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
        }
        .muted { color: var(--muted); }
        .badge {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            background: #1e3a5f;
            color: #93c5fd;
        }
        .badge-admin { background: #134e4a; color: #5eead4; }
        .page-wide { max-width: 1100px; }
        .nav-links { display: flex; gap: .75rem; align-items: center; }
        .nav-links a {
            color: var(--muted);
            font-size: .9rem;
            text-decoration: none;
            padding: .35rem .6rem;
            border-radius: 8px;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--text);
            background: #222a35;
            text-decoration: none;
        }
        .table-wrap { overflow-x: auto; }
        table.logs {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }
        table.logs th, table.logs td {
            text-align: left;
            padding: .75rem .65rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        table.logs th {
            color: var(--muted);
            font-weight: 600;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        table.logs tr:hover td { background: #1b232e; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; }
        .empty { padding: 2rem; text-align: center; color: var(--muted); }
        .pager { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; flex-wrap: wrap; }
        .pager a, .pager span {
            padding: .4rem .7rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            font-size: .85rem;
        }
        .pager a:hover { color: var(--text); text-decoration: none; }
        .pager .active { color: var(--text); border-color: var(--accent); }
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .stat {
            background: #121820;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: .9rem 1rem;
        }
        .stat .label { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; }
        .stat .value { font-size: 1.4rem; font-weight: 650; margin-top: .25rem; }
    </style>
</head>
<body>
    @yield('body')
</body>
</html>
