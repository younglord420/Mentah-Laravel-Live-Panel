@extends('layouts.app')

@section('title', 'Login Admin')

@section('body')
<div class="shell">
    <div class="card">
        <p class="eyebrow">Admin</p>
        <h1>Masuk Admin</h1>
        <p class="sub">Area khusus administrator.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <div class="row">
                <label>
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Ingat saya
                </label>
            </div>
            <button class="btn btn-admin" type="submit">Login Admin</button>
        </form>
    </div>
</div>
@endsection
