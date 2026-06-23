@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email-message">
    <p class="verify-email-message__text">登録していただいたメールアドレスに認証メールを送付しました。</p>

    <p class="verify-email-message__text">メール認証を完了してください。</p>
</div>

<div class="verify-email-button">
    <a href="https://mailtrap.io/inboxes" target="_blank" class="verify-email-button__link">
        認証はこちらから
    </a>
</div>

<form method="POST" action="{{ route('verification.send') }}" class="verify-email-form">
    @csrf
    <button type="submit" class="verify-email-form__button">認証メールを再送する</button>
</form>
@endsection

