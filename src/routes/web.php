<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreakTimeController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::middleware('auth')->group(function () {
    // 一般ユーザー
    Route::get('/', [AttendanceController::class, 'attendance']);
    Route::get('/attendance', [AttendanceController::class, 'attendance'])
        ->name('attendance.create');
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])
        ->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.detail');
    Route::get('/stamp_correction_request/list', [RequestController::class, 'index']);

    Route::post('/attendance/create', [AttendanceController::class, 'create']);
    Route::post('/attendance/update/work_end', [AttendanceController::class, 'workFinished']);

    Route::post('/brake_time/create', [BreakTimeController::class, 'create']);
    Route::post('/brake_time/update/break_end', [BreakTimeController::class, 'breakFinished']);

    Route::post('/request/create', [RequestController::class, 'create']);
});

// 管理者
//Route::get('/admin/login', [AdminController::class, 'AttendanceList']);
Route::get('/admin/login', [AuthController::class, 'adminShowLogin']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::middleware('admin')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('attendance/list', [AdminController::class, 'AttendanceList'])
        ->name('admin.attendance.list');
    });

    // 一般ユーザーと同じURLを使用
    Route::get('/stamp_correction_request/list', [AdminController::class, 'RequestIndex']);
});