@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/requests/index.css') }}">
@endsection

@section('content')

<h2 class="title">申請一覧</h2>

<nav class="request-nav">
    @if($tab === 'pending')
        <a href="/stamp_correction_request/list" class="request-nav__link request-nav__link--active">承認待ち</a>
        <a href="/stamp_correction_request/list/?type=approved" class="request-nav__link">承認済み</a>
    @elseif($tab === 'approved')
        <a href="/stamp_correction_request/list" class="request-nav__link">承認待ち</a>
        <a href="/stamp_correction_request/list/?type=approved" class="request-nav__link request-nav__link--active">承認済み</a>
    @endif
</nav>

<table class="request-table">
    <!-- 名前 -->
    <tr class="request-table__row">
        <th class="request-table__header"></th>
        <th class="request-table__header">名前</th>
        <th class="request-table__header">対象日時</th>
        <th class="request-table__header">申請理由</th>
        <th class="request-table__header">申請日時</th>
        <th class="request-table__header">詳細</th>
    </tr>
    @foreach($attendanceCorrections as $attendanceCorrection)
        <tr class="request-table__row">
            <td class="request-table__data">{{ $statusMessage[$attendanceCorrection->status] }}</td>
            <td class="request-table__data">{{ $attendanceCorrection->user->name }}</td>
            <td class="request-table__data">{{ $attendanceCorrection->attendance->work_date->isoFormat('Y/M/D') }}</td>
            <td class="request-table__data">{{ $attendanceCorrection->reason }}</td>
            <td class="request-table__data">{{ $attendanceCorrection->created_at->isoFormat('Y/M/D') }}</td>
            <td class="request-table__data">
                <a href="/attendance/detail/{{ $attendanceCorrection->attendance->id }}" class="request-table__link">詳細</a>
            </td>
        </tr>
    @endforeach
</table>

@endsection