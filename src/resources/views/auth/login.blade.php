@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')

<h2 class="title">
    ログイン
</h2>

<form action="/login" method="POST" class="login-form">
    @csrf
    <div class="login-form__item">
        <label for="email" class="login-form__label">メールアドレス</label>
        <input type="email" class="login-form__input" name="email" id="email">
    </div>

    <div class="login-form__item">
        <label for="password" class="login-form__label">パスワード</label>
        <input type="password" class="login-form__input" name="password" id="password">
    </div>

    <div class="login-form__button">
        <button class="login-form__submit">
            ログインする
        </button>
    </div>
</form>

<a href="/register" class="register-link">会員登録はこちら</a>

@endsection