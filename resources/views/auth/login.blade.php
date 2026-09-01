@extends('layouts.app')

@section('title', 'Login User')

@section('body')
<div class="shell">
    <div class="card">
        <p class="eyebrow">User</p>
        <h1>Masuk</h1>
        <p class="sub">Login untuk akun user biasa.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
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
            <button class="btn" type="submit">Login</button>
        </form>
    </div>
</div>
@endsection
