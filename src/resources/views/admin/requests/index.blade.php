@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/requests/index.css') }}">
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

<table class="attendance-table">
    <!-- foreach -->
    <tr class="attendance-table__row">
        <th class="attendance-table__header">状態</th>
        <th class="attendance-table__header">名前</th>
        <th class="attendance-table__header">対象日時</th>
        <th class="attendance-table__header">申請理由</th>
        <th class="attendance-table__header">申請日時</th>
        <th class="attendance-table__header">詳細</th>
    </tr>
    @foreach($requests as $request)
    <tr class="attendance-table__row">
        <td class="attendance-table__data">
            {{ $status[$request->status] }}
        </td>

        <td class="attendance-table__data">
            {{ $request->user->name }}
        </td>

        <td class="attendance-table__data">
            {{ $request->attendance->work_date->isoFormat('Y/M/D') }}
        </td>

        <td class="attendance-table__data">
            {{ $request->reason }}
        </td>

        <td class="attendance-table__data">
            {{ $request->created_at->isoFormat('Y/M/D') }}
        </td>
        
        <td class="attendance-table__data">
            <a href="/admin/stamp_correction_request/approve/{{ $request->id }}" class="attendance-table__detail">詳細</a>
        </td>
    </tr>
    @endforeach
</table>

@endsection