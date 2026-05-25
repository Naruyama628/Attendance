@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendances/index.css') }}">
@endsection

@section('content')

<h2 class="title">勤怠一覧</h2>

<div class="month-nav">
    <a href="{{ route('admin.attendance.list', ['month' => $currentMonth->copy()->subDay()->format('Y-m-d')]) }}" class="month-nav__previous">← 前日</a>

    <span class="month-nav__current">{{ $currentMonth->format('Y/m/d') }}</span>

    <a href="{{ route('admin.attendance.list', ['month' => $currentMonth->copy()->addDay()->format('Y-m-d')]) }}" class="month-nav__next">翌日 →</a>
</div>

<table class="attendance-table">
    <!-- foreach -->
    <tr class="attendance-table__row">
        <th class="attendance-table__header">名前</th>
        <th class="attendance-table__header">出勤</th>
        <th class="attendance-table__header">退勤</th>
        <th class="attendance-table__header">休憩</th>
        <th class="attendance-table__header">合計</th>
        <th class="attendance-table__header">詳細</th>
    </tr>
    @foreach($attendances as $attendance)
    <tr class="attendance-table__row">
        <td class="attendance-table__data">{{ $attendance->user->name }}</td>

        <td class="attendance-table__data">{{ optional($attendance->clock_in)->format('H:i') }}</td>

        <td class="attendance-table__data">{{ optional($attendance->clock_out)->format('H:i')  }}</td>

        <td class="attendance-table__data">{{ $attendance->totalBreakTime }}</td>

        <td class="attendance-table__data">{{ $attendance->totalWorkTime }}</td>
        
        <td class="attendance-table__data">
            @if($attendance)
                <a href="/attendance/detail/{{ $attendance->id }}" class="attendance-table__detail">詳細</a>
            @endif
        </td>
    </tr>
    @endforeach
</table>

@endsection