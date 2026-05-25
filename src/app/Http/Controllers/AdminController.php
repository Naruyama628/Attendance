<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\BreakTime;

class AdminController extends Controller
{
    //

    public function attendanceList(Request $request) {
        $month = $request->query('month', now()->format('Y-m'));

        $currentMonth = Carbon::parse($month . '-01');

        $dates = CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth(),
        );

        $attendances = Attendance::whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->whereDay('work_date', $currentMonth->day)
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

        return view('admin.attendances.index', compact('dates', 'attendances', 'currentMonth'));
    }

    public function RequestIndex(Request $request) {
        $month = $request->query('month', now()->format('Y-m'));

        $currentMonth = Carbon::parse($month . '-01');

        $dates = CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth(),
        );

        $attendances = Attendance::whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->whereDay('work_date', $currentMonth->day)
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

        return view('admin.attendances.index', compact('dates', 'attendances', 'currentMonth'));
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
