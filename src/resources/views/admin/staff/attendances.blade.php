@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/attendance.css') }}">
@endsection

@section('content')

<h2 class="title">{{ $user->name }}の勤怠</h2>

<div class="month-nav">
    <a href="{{ route('admin.staff.attendance', [
    'id'=>$user->id,
    'month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="month-nav__previous">← 前日</a>

    <span class="month-nav__current">{{ $currentMonth->format('Y/m') }}</span>

    <a href="{{ route('admin.staff.attendance', [
    'id'=>$user->id,
    'month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="month-nav__next">翌日 →</a>
</div>

<table class="attendance-table">
    <!-- foreach -->
    <tr class="attendance-table__row">
        <th class="attendance-table__header">日付</th>
        <th class="attendance-table__header">出勤</th>
        <th class="attendance-table__header">退勤</th>
        <th class="attendance-table__header">休憩</th>
        <th class="attendance-table__header">合計</th>
        <th class="attendance-table__header">詳細</th>
    </tr>
    @foreach($dates as $date)
    <tr class="attendance-table__row">
        @php
            $attendance = $attendances[$date->format('Y-m-d')] ?? null;
        @endphp
        <td class="attendance-table__data">{{ $date->isoFormat('MM/DD(ddd)')}}</td>

        <td class="attendance-table__data">{{ $attendance && $attendance->clock_in ? $attendance->clock_in->format('H:i') : '' }}</td>

        <td class="attendance-table__data">{{ $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td>

        <td class="attendance-table__data">{{ $attendance && $attendance->totalBreakTime ? $attendance->totalBreakTime : '' }}</td>

        <td class="attendance-table__data">{{ $attendance && $attendance->totalWorkTime ? $attendance->totalWorkTime : '' }}</td>
        
        <td class="attendance-table__data">
            @if($attendance)
                <a href="/admin/attendance/detail/{{ $attendance->id }}" class="attendance-table__detail">詳細</a>
            @endif
        </td>
    </tr>
    @endforeach
</table>

<div class="csv-button-wrapper">
    <a href="{{ route('admin.staff.attendance.csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
       class="csv-button">
        CSV出力
    </a>
</div>
@endsection