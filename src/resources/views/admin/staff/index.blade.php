@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css') }}">
@endsection

@section('content')

<h2 class="title">スタッフ一覧</h2>

<table class="attendance-table">
    <!-- foreach -->
    <tr class="attendance-table__row">
        <th class="attendance-table__header">名前</th>
        <th class="attendance-table__header">メールアドレス</th>
        <th class="attendance-table__header">月次勤怠</th>
    </tr>
    @foreach($staffs as $staff)
    <tr class="attendance-table__row">
        <td class="attendance-table__data">
            {{ $staff->name }}
        </td>

        <td class="attendance-table__data">
            {{ $staff->email }}
        </td>
        
        <td class="attendance-table__data">
            <a href="/admin/attendance/staff/{{$staff->id}}" class="attendance-table__detail">詳細</a>
        </td>
    </tr>
    @endforeach
</table>

@endsection