@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendances/show.css') }}">
<link rel="stylesheet" href="{{ asset('css/layouts/error.css') }}">
@endsection

@section('content')

<h2 class="title">勤怠詳細</h2>

<form action="/admin/correction/createApproval" method="post" class="request-form">
    @csrf
    <table class="attendance-detail-table">
        <!-- 名前 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">名前</th>
            <td class="attendance-detail-table__data">
                {{ $attendance->user->name }}
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
                {{ $attendance->work_date->isoFormat('YYYY年') }}
            </td>
            <td class="attendance-detail-table__data attendance-detail-table__data--separator">
            </td>
            <td class="attendance-detail-table__data">
                {{ $attendance->work_date->isoFormat('M月D日') }}
            </td>
            <td class="attendance-detail-table__data attendance-detail-table__data--empty"></td>
        </tr>

        <!-- 出勤退勤 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">出勤・退勤</th>
            @if(optional($correctionRequest)->status === 'pending')
                <!-- 修正申請済み -->
                <td class="attendance-detail-table__data">
                   {{ optional($correctionRequest?->requested_clock_in)->format('H:i') }}
                </td>

                <td class="attendance-detail-table__data
                attendance-detail-table__data--separator">～</td>

                <td class="attendance-detail-table__data">{{ optional($correctionRequest?->requested_clock_out)->format('H:i') }}</td>
            @else
                <!-- 修正申請がないとき -->
                <td class="attendance-detail-table__data">
                    <input type="time" name="work_start" 
                    value="{{ old('work_start', optional($attendance->clock_in)->format('H:i')) }}"  class="attendance-detail-table__input">
                    @error('work_start')
                        <div class="form__error">{{ $message }}</div>
                    @enderror
                </td>

                <td class="attendance-detail-table__data
                attendance-detail-table__data--separator">～</td>

                <td class="attendance-detail-table__data">
                    <input type="time" name="work_end" 
                    value="{{ old('work_end', optional($attendance->clock_out)->format('H:i')) }}"  class="attendance-detail-table__input">
                    @error('work_end')
                        <div class="form__error">{{ $message }}</div>
                    @enderror
                </td>
            @endif
            <td class="attendance-detail-table__data
            attendance-detail-table__data--empty"></td>
        </tr>

        <!-- 休憩 -->
        @if(optional($correctionRequest)->status === 'pending')
            @foreach($attendance->breaks as $break)
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">
                        {{ $loop->first ? '休憩' : '休憩' . $loop->iteration }}
                    </th>

                    <td class="attendance-detail-table__data">
                        {{ $correctionRequest
                            ->breakCorrectionRequests
                            ->where('break_time_id', $break->id)
                            ->first()
                            ->requested_break_start
                            ->format('H:i') }}
                    </td>
                    <td class="attendance-detail-table__data
                    attendance-detail-table__data--separator">～</td>
                    <td class="attendance-detail-table__data">
                        {{ $correctionRequest
                            ->breakCorrectionRequests
                            ->where('break_time_id', $break->id)
                            ->first()
                            ->requested_break_end
                            ->format('H:i') }}
                    </td>
                    </td>
                    <td class="attendance-detail-table__data
                    attendance-detail-table__data--empty"></td>
                </tr>
            @endforeach
        @else
            @foreach($attendance->breaks as $break)
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">
                        {{ $loop->first ? '休憩' : '休憩' . $loop->iteration }}
                    </th>

                    <td class="attendance-detail-table__data">
                        <input type="time" name="break_start[]" 
                        value="{{ old('break_start.' . $loop->index, optional($break->break_start)->format('H:i')) }}"  class="attendance-detail-table__input">
                        @error('break_start.' . $loop->index)
                            <div class="form__error">{{ $message }}</div>
                        @enderror
                    </td>
                    <td class="attendance-detail-table__data
                    attendance-detail-table__data--separator">～</td>
                    <td class="attendance-detail-table__data">
                        <input type="time" name="break_end[]" 
                        value="{{ old('break_end.' . $loop->index, optional($break->break_end)->format('H:i')) }}"  class="attendance-detail-table__input">
                        @error('break_end.' . $loop->index)
                            <div class="form__error">{{ $message }}</div>
                        @enderror
                    </td>
                    <td class="attendance-detail-table__data
                    attendance-detail-table__data--empty"></td>
                </tr>

                <input type="hidden" name="break_id[]" value="{{ $break->id }}">
            @endforeach
        @endif

        @if(optional($correctionRequest)->status !== 'pending')
        <!-- 休憩 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">
                休憩{{ $attendance->breaks->count() + 1 }}
            </th>
            <td class="attendance-detail-table__data">
                <input type="time" name="break_start_create" 
                value="{{ old('break_start_create')}}"
                class="attendance-detail-table__input">
                @error('break_start_create')
                        <div class="form__error">{{ $message }}</div>
                @enderror
            </td>
            <td class="attendance-detail-table__data
            attendance-detail-table__data--separator">～
            </td>
            <td class="attendance-detail-table__data">
                <input type="time" name="break_end_create" 
                value="{{ old('break_end_create')}}"
                class="attendance-detail-table__input">
                @error('break_end_create')
                        <div class="form__error">{{ $message }}</div>
                @enderror
            </td>
            <td class="attendance-detail-table__data
            attendance-detail-table__data--empty"></td>
        </tr>
        @endif

        <!-- 備考 -->
        <tr class="attendance-detail-table__row">
            <th class="attendance-detail-table__header">備考</th>
            @if(optional($correctionRequest)->status === 'pending')
            <td class="attendance-detail-table__data
                attendance-detail-table__data--remarks"
                colspan="4">
                {{ $correctionRequest->reason }}
            </td>
            @else
                <td class="attendance-detail-table__data
                attendance-detail-table__data--remarks"
                colspan="4">
                    <textarea name="remarks" 
                    class="attendance-detail-table__textarea">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div class="form__error">{{ $message }}</div>
                    @enderror
                </td>
            @endif
        </tr>
    </table>

    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

    @if(optional($correctionRequest)->status === 'pending')
    <div class="request-form__notice">
        *承認待ちのため修正はできません。
    </div>
    @else
    <div class="request-form__button">
        <button class="request-form__button--submit">修正</button>
    </div>
    @endif
</form>
@endsection