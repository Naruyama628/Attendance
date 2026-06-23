<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreakTimeController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminController;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

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

// 認証案内画面
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 認証リンククリック処理
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    if (! auth()->user()->profile) {
        return redirect('/attendance');
    }
})->middleware(['auth', 'signed'])->name('verification.verify');

// 再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', '認証メールを再送しました');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function () {
    // 一般ユーザー
    Route::get('/', [AttendanceController::class, 'attendance']);
    Route::get('/attendance', [AttendanceController::class, 'attendance'])
        ->name('attendance.create');
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])
        ->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.detail');

    Route::post('/attendance/create', [AttendanceController::class, 'create']);
    Route::post('/attendance/update/work_end', [AttendanceController::class, 'workFinished']);

    Route::post('/brake_time/create', [BreakTimeController::class, 'create']);
    Route::post('/brake_time/update/break_end', [BreakTimeController::class, 'breakFinished']);

    Route::post('/request/create', [RequestController::class, 'create']);

    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])
        ->middleware(['role'])
        ->name('stamp_correction_request.list');
});

// 管理者
Route::get('/admin/login', [AuthController::class, 'adminShowLogin']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::middleware(['admin', 'verified'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('attendance/list', [AdminController::class, 'AttendanceList'])
        ->name('admin.attendance.list');

        Route::get('/attendance/detail/{id}', [AdminController::class, 'attendanceDetail'])
        ->name('admin.attendance.detail');

        Route::get('staff/list', [AdminController::class, 'staffList']);
        
        Route::get('attendance/staff/{id}', [AdminController::class, 'staffAttendanceList'])
            ->name('admin.staff.attendance');

        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'approveAttendanceCorrection'])
        ->name('stamp_correction_request.approve');

        Route::post('/correction/approval', [AdminController::class, 'correctionApprove']);

        Route::post('/correction/createApproval', [AdminController::class, 'createCorrectionApprove']);
        
        Route::get(
            '/admin/attendance/staff/{id}/csv',
            [AdminController::class, 'exportCsv']
        )->name('admin.staff.attendance.csv');
    });
});
