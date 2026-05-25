@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/create.css') }}">
@endsection

@section('content')

<div class="work-status">
    @if($status === 'before_work')
        勤務外
    @elseif($status === 'working')
        出勤中
    @elseif($status === 'breaking')
        休憩中
    @elseif($status === 'finished')
        退勤済
    @endif
</div>

<div class="today">
    {{ $dt->isoFormat('YYYY年MM月DD日(ddd)') }}
</div>

<div class="current_time">
    {{ $dt->isoFormat('HH:mm') }}
</div>

<div class="attendance-button">
    @if($status === 'before_work')
        <!-- 出勤 -->
        <form action="/attendance/create" method="post" class="attendance-form">
            @csrf
            <button class="attendance-form__button">出勤</button>
        </form>
    @elseif($status === 'working')
        <!-- 退勤 -->
        <form action="/attendance/update/work_end" method="post" class="attendance-form">
            @csrf
            <button class="attendance-form__button">退勤</button>
        </form>

        <!-- 休憩 -->
        <form action="/brake_time/create" method="post" class="attendance-form">
            @csrf
            <button class="attendance-form__button--white">休憩入</button>
        </form>
    @elseif($status === 'breaking')
        <!-- 休憩終わり -->
        <form action="/brake_time/update/break_end" method="post" class="attendance-form">
            @csrf
            <button class="attendance-form__button--white">休憩戻</button>
        </form>
    @elseif($status === 'finished')
        <p class="attendance__finished-text">お疲れさまでした。</p>
    @endif
</div>
@endsection