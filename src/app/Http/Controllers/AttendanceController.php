<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;

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

        $user = Auth()->user();
        if($user->role === 'admin')
        {
            return redirect()->route('admin.attendance.list');            
        } else {
            return view('attendances.create', compact('dt', 'status'));
        }
    }

    public function attendanceDetail(Request $request) {
        $attendance = $this->getCorrectedAttendance($request->id);

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
        ->where('status', 'pending')
        ->latest()
        ->first();

        if($correctionRequest) {
            $additionalBreak = BreakCorrectionRequest::where('attendance_correction_id', $correctionRequest->id)
                ->where('break_time_id', null)
                ->first();

            if ($additionalBreak) {
                $attendance->breaks->push(
                    new BreakTime([
                        'break_start' => $additionalBreak->requested_break_start,
                        'break_end' => $additionalBreak->requested_break_end,
                    ])
                );
            }
        }

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

        $attendances = $attendances->map(function ($attendance) {
            return $this->getCorrectedAttendance($attendance->id);
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
    public function getCorrectedAttendance($attendance_id)
    {
        $attendance = Attendance::find($attendance_id);

        $attendanceCorrection = AttendanceCorrectionRequest::where('attendance_id', $attendance_id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if ($attendanceCorrection) {
            $attendance->clock_in = $attendanceCorrection->requested_clock_in;
            $attendance->clock_out = $attendanceCorrection->requested_clock_out;
        }

        $breaks = $attendance->breaks()->get();

        foreach($breaks as $break) {
            $breakCorrection = BreakCorrectionRequest::where('break_time_id', $break->id)
            ->whereHas('attendanceCorrectionRequest', function ($query) {
                $query->where('status', 'approved');
            })
            ->latest()->first();

            if($breakCorrection) {
                $break->break_start = $breakCorrection->requested_break_start;
                $break->break_end = $breakCorrection->requested_break_end;
            }
        }

        $attendance->setRelation('breaks', $breaks);
        return $attendance;
    }

    private function totalWorkMinutes($attendance)
    {
        if (!$attendance->clock_in || !$attendance->clock_out) {
            $attendance->totalWorkTime = '0:00';
            return;
        }

        $workMinutes = $attendance->clock_in
            ->diffInMinutes($attendance->clock_out);

        $breakMinutes = 0;

        foreach ($attendance->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $breakMinutes += $break->break_start
                    ->diffInMinutes($break->break_end);
            }
        }

        $workMinutes -= $breakMinutes;

        $attendance->totalWorkTime = sprintf(
            '%d:%02d',
            floor($workMinutes / 60),
            $workMinutes % 60
        );
    }

    private function totalBreakMinutes($attendance)
    {
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)
            ->get();
        $totalBreakMinutes = 0;
        foreach($breakTimes as $breakTime) {
            $correctionBreak = BreakCorrectionRequest::where('break_time_id', $breakTime->id)
                ->whereHas('attendanceCorrectionRequest', function ($query) {
                        $query->where('status', 'approved');
                })
                ->latest()
                ->first();

            if($correctionBreak) {
                $breakTime->break_start = $correctionBreak->requested_break_start;
                $breakTime->break_end = $correctionBreak->requested_break_end;
            }

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
