@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
<link rel="stylesheet" href="{{ asset('css/layouts/error.css') }}">
@endsection

@section('content')

<h2 class="title">
    管理者ログイン
</h2>

<form action="/admin/login" method="POST" class="login-form"  novalidate>
    @csrf
    <div class="login-form__item">
        <label for="email" class="login-form__label">メールアドレス</label>
        <input type="email" class="login-form__input" name="email" id="email" value="{{ old('email') }}">
        @error('email')
            <p class="form__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="login-form__item">
        <label for="password" class="login-form__label">パスワード</label>
        <input type="password" class="login-form__input" name="password" id="password">
        @error('password')
            <p class="form__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="login-form__button">
        <button type="submit" class="login-form__submit">
            管理者ログインする
        </button>
    </div>
</form>

@endsection