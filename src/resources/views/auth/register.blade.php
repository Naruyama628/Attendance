@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')

<h2 class="title">
    会員登録
</h2>

<form action="/register" method="POST" class="register-form">
    @csrf
    <div class="register-form__item">
        <label for="name" class="register-form__label">名前</label>
        <input type="text" class="register-form__input" name="name" id="name">
    </div>

    <div class="register-form__item">
        <label for="email" class="register-form__label">メールアドレス</label>
        <input type="email" class="register-form__input" name="email" id="email">
    </div>

    <div class="register-form__item">
        <label for="password" class="register-form__label">パスワード</label>
        <input type="password" class="register-form__input" name="password" id="password">
    </div>

    <div class="register-form__item">
        <label for="password_confirmation" class="register-form__label">パスワード確認</label>
        <input type="password" class="register-form__input" name="password_confirmation" id="password_confirmation">
    </div>

    <div class="register-form__button">
        <button type="submit" class="register-form__submit">
            登録する
        </button>
    </div>
</form>

<a href="/login" class="register-link">ログインはこちら</a>

@endsection