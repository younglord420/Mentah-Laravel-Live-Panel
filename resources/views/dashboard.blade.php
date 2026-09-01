@extends('layouts.app')

@section('title', 'Dashboard')

@section('body')
<div class="topbar">
    <div>
        <strong>{{ auth('user')->user()->name }}</strong>
        <span class="badge">user</span>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn-ghost" type="submit">Logout</button>
    </form>
</div>
<div class="page">
    <div class="panel">
        <h1>Dashboard User</h1>
        <p class="muted">Selamat datang, {{ auth('user')->user()->name }}. Login user tidak memakai database.</p>
    </div>
</div>
@endsection
