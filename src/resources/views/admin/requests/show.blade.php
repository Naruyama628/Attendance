@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/requests/show.css') }}">
<link rel="stylesheet" href="{{ asset('css/layouts/error.css') }}">
@endsection

@section('content')

<h2 class="title">勤怠詳細</h2>

<form action="/admin/correction/
approval" method="post" class="request-form">
    @csrf
    <table class="attendance-detail-table">
        <!-- 名前 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">名前</th>
            <td class="attendance-detail-table__data">
                {{ $collection->user->name }}
            </td>
            <td class="attendance-detail-table__data attendance-detail-table__data--separator">
            </td>
            <td class="attendance-detail-table__data"></td>
            <td class="attendance-detail-table__data attendance-detail-table__data--empty"></td>
        </tr>

        <!-- 日付 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">日付</th>
            <td class="attendance-detail-table__data">
                {{ $collection->attendance->work_date->isoFormat('YYYY年') }}
            </td>
            <td class="attendance-detail-table__data attendance-detail-table__data--separator">
                
            </td>
            <td class="attendance-detail-table__data">
                {{ $collection->attendance->work_date->isoFormat('M月D日') }}
            </td>
            <td class="attendance-detail-table__data attendance-detail-table__data--empty"></td>
        </tr>

        <!-- 出勤退勤 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">出勤・退勤</th>

            <!-- 修正申請済み -->
            <td class="attendance-detail-table__data">
                {{ $collection->requested_clock_in->format('H:i') }}
            </td>

            <td class="attendance-detail-table__data
            attendance-detail-table__data--separator">～</td>

            <td class="attendance-detail-table__data">
                {{ $collection->requested_clock_out->format('H:i') }}
            </td>
            
            <td class="attendance-detail-table__data
            attendance-detail-table__data--empty"></td>
        </tr>

        @foreach($collection->breakCorrectionRequests as $break)
            
        @endforeach

        <!-- 休憩 -->
        @foreach($collection->breakCorrectionRequests as $break)
            <tr class="attendance-detail-table__row">
                <th class="attendance-detail-table__header">
                    {{ $loop->first ? '休憩' : '休憩' . $loop->iteration }}
                </th>


                <td class="attendance-detail-table__data">
                    {{ $break->requested_break_start->format('H:i') }}
                </td>
                <td class="attendance-detail-table__data
                attendance-detail-table__data--separator">～</td>
                <td class="attendance-detail-table__data">
                    {{ $break->requested_break_end->format('H:i') }}
                </td>
                <td class="attendance-detail-table__data
                attendance-detail-table__data--empty"></td>
            </tr>
        @endforeach

        <!-- 備考 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">備考</th>

            <td class="attendance-detail-table__data
                attendance-detail-table__data--remarks"
                colspan="4">
                {{ $collection->reason }}
            </td>
        </tr>
    </table>

    <input type="hidden" name="attendance_id" value="{{ $collection->attendance->id }}">

    <div class="request-form__button">
        @if($collection->status === 'approved')
            <div class="request-form__button--approved">承認済み</div>
        @elseif($collection->status === 'pending')
            <button class="request-form__button--submit">承認</button>
        @endif
    </div>
</form>
@endsection