<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\AttendanceCorrectionRequest;

class AttendanceController extends Controller
{
    //
    public function attendance() {
        Carbon::setLocale('ja');
        $dt = Carbon::now();

        // 出勤済みかどうかを判定
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', today())
            ->first();

        $status = 'before_work';
        if($attendance) {
            // 出勤済み
            $isBraking = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->exists();
            if($attendance->clock_in && is_null($attendance->clock_out)) {
                if($isBraking) {
                    // 休憩中
                    $status = 'breaking';
                } else {
                    // 出勤中
                    $status = 'working';
                }
            } elseif($attendance->clock_out) {
                // 退勤済み
                $status = 'finished';
            }
        }

        return view('attendances.create', compact('dt', 'status'));
    }

    public function attendanceDetail(Request $request) {
        $attendance = Attendance::find($request->id);

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
        ->first();

        return view('attendances.show', compact('attendance', 'correctionRequest'));
    }

    public function attendanceList(Request $request) {
        $month = $request->query('month', now()->format('Y-m'));

        $currentMonth = Carbon::parse($month . '-01');

        $dates = CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth(),
        );

        $attendances = Attendance::where('user_id', auth()->id())
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($attendance){
                return $attendance->work_date->format('Y-m-d');
            });

        foreach($attendances as $attendance) {
            // 合計休憩時間計算
            $this->totalBreakMinutes($attendance);

            // 合計勤務時間計算
            $this->totalWorkMinutes($attendance);
        }

        return view('attendances.index', compact('dates', 'attendances', 'currentMonth'));
    }

    public function create(Request $request) {
        Attendance::create([
            'user_id' => auth()->id(),
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function workFinished(Request $request) {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', today())
            ->first();

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    // 共通化処理
    private function totalWorkMinutes($attendance)
    {
        $totalWorkMinutes = 0;
        $attendance->totalWorkTime = '0:00';
        if($attendance->clock_in && $attendance->clock_out) {
            $totalWorkMinutes = 0;
            $totalWorkMinutes = $attendance->clock_in
                ->diffInMinutes($attendance->clock_out);

                
            $attendance->totalWorkTime = sprintf(
                '%d:%02d',
                floor($totalWorkMinutes / 60),
                $totalWorkMinutes % 60
            );
        }
    }

    private function totalBreakMinutes($attendance)
    {
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)
            ->get();
        $totalBreakMinutes = 0;
        foreach($breakTimes as $breakTime) {
            if (!$breakTime->break_start || !$breakTime->break_end) {
                continue;
            }

            $totalBreakMinutes += $breakTime->break_start
                ->diffInMinutes($breakTime->break_end);
        }

        $attendance->totalBreakTime = sprintf(
            '%d:%02d',
            floor($totalBreakMinutes / 60),
            $totalBreakMinutes % 60
        );
    }
}
