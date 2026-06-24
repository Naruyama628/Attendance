<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;
use App\Models\User;
use App\Http\Requests\CorrectionRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    //

    // 勤怠一覧
    public function attendanceList(Request $request) {
        $month = $request->query('month', now()->format('Y-m-d'));

        $currentMonth = Carbon::parse($month . '-01');

        $dates = CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth(),
        );

        $attendances = Attendance::whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->whereDay('work_date', $currentMonth->day)
            ->get();
        
        $attendances = $attendances->map(function ($attendance) {
            return $this->getCorrectedAttendance($attendance->id);
        });
        
        foreach($attendances as $attendance) {
            // 合計休憩時間計算
            $this->totalBreakMinutes($attendance);

            // 合計勤務時間計算
            $this->totalWorkMinutes($attendance);

        }

        return view('admin.attendances.index', compact('dates', 'attendances', 'currentMonth'));
    }

    public function attendanceDetail($id) {
        $attendance = $this->getCorrectedAttendance($id);

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
        
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
        return view('admin.attendances.show', compact('attendance', 'correctionRequest'));
    }

    public function requestIndex(Request $request) {
        $status = [
            'pending' => '承認待ち',
            'approved' => '承認済み',
        ];

        if($request->type == 'approved') {
            $tab = 'approved';
            $requests = AttendanceCorrectionRequest::where('status', 'approved')
                ->get();
        } else {
            $tab = 'pending';
            $requests = AttendanceCorrectionRequest::where('status', 'pending')
                ->get();
        }
        return view('admin.requests.index', compact('requests', 'status', 'tab'));
    }

    public function staffList(Request $request) {
        $staffs = User::where('role', 'user')
            ->get();

        return view('admin.staff.index', compact('staffs'));
    }

    public function staffAttendanceList(Request $request, $id) {
        $user = User::find($id);
        $month = $request->query('month', now()->format('Y-m'));

        $currentMonth = Carbon::parse($month . '-01');
        $dates = CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth(),
        );

        $attendances = Attendance::where('user_id', $id)
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

        return view('admin.staff.attendances', compact('attendances', 'user', 'dates', 'currentMonth'));
    }

    public function approveAttendanceCorrection($attendance_correct_request_id) {
        $collection = AttendanceCorrectionRequest::find($attendance_correct_request_id);
        
        return view('admin.requests.show', compact('collection'));
    }

    public function correctionApprove(Request $request)
    {
        $attendanceRequest = AttendanceCorrectionRequest::where(
        'attendance_id',
        $request->attendance_id)
        ->where('status', 'pending')
        ->first();
        
        $attendanceRequest->update([
            'status' => 'approved',
            'approved_by' => Auth()->user()->id,
            'approved_at' => now(),
        ]);

        $breakCorrection = BreakCorrectionRequest::where('attendance_correction_id', $attendanceRequest->id)
            ->where('break_time_id', null)
            ->first();

        if($breakCorrection){
            $break = BreakTime::create([
                'attendance_id' => $request->attendance_id,
                'break_start' => $breakCorrection->requested_break_start,
                'break_end' => $breakCorrection->requested_break_end,
            ]);
            $breakCorrection->update([
                'break_time_id' => $break->id,
            ]);
        }

        return redirect()->route('stamp_correction_request.list');

    }

    public function createCorrectionApprove(CorrectionRequest $request)
    {
        $correction = (object)[
                'attendance_id' => $request->attendance_id,
                'work_start' => $request->work_start,
                'work_end' => $request->work_end,
                'remarks' => $request->remarks,
                'break_start' => $request->break_start,
                'break_end' => $request->break_end,
                'break_id' => $request->break_id,
                'break_end_create' => $request->break_end_create,
                'break_start_create' => $request->break_start_create,
            ];
            $attendanceRequest = $this->requestCreate($correction);
            $attendanceRequest->update([
                'status' => 'approved',
                'approved_by' => Auth()->user()->id,
                'approved_at' => now(),
            ]);

            $breakCorrection = BreakCorrectionRequest::where('attendance_correction_id', $attendanceRequest->id)
                ->where('break_time_id', null)
                ->first();

            if($breakCorrection){
                 $break = BreakTime::create([
                    'attendance_id' => $request->attendance_id,
                    'break_start' => $breakCorrection->requested_break_start,
                    'break_end' => $breakCorrection->requested_break_end,
                ]);
                $breakCorrection->update([
                    'break_time_id' => $break->id,
                ]);
            }

            return redirect()->route('stamp_correction_request.list');
    }

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

    public function exportCsv($id, Request $request)
    {
        $user = User::findOrFail($id);

        $currentMonth = Carbon::parse($request->month);

        $attendances = Attendance::where('user_id', $id)
            ->orderBy('work_date')
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get();

        $response = new StreamedResponse(function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            // ヘッダー
            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            $attendances = $attendances->map(function ($attendance) {
                return $this->getCorrectedAttendance($attendance->id);
            });

            foreach ($attendances as $attendance) {
            $this->totalBreakMinutes($attendance);
            $this->totalWorkMinutes($attendance);
            fputcsv($handle, [
                    $attendance->work_date->format('Y-m-d'),
                    optional($attendance->clock_in)->format('H:i'),
                    optional($attendance->clock_out)->format('H:i'),
                    $attendance->totalBreakTime,
                    $attendance->totalWorkTime,
                ]);
            }

            fclose($handle);
        });

        $fileName = $user->name . '月次勤怠.csv';

        $response->headers->set(
            'Content-Type',
            'text/csv'
        );

        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="'.$fileName.'"'
        );

        return $response;
    }

    // 共通化処理
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
                ->latest()->first();
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

    public function requestCreate($correction)
    {
        $attendance = Attendance::find($correction->attendance_id);
        $work_start = \Carbon\Carbon::parse($attendance->clock_in->format('Y-m-d') . ' ' . $correction->work_start);

        $work_end = \Carbon\Carbon::parse
        ($attendance->clock_in->format('Y-m-d') . ' ' . $correction->work_end);

        $attendance_correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $correction->attendance_id,
            'user_id' => auth()->id(),
            'requested_clock_in' => $work_start,
            'requested_clock_out' => $work_end,
            'reason' => $correction->remarks,
        ]);

        $index = 0;
        if($correction->break_start) {
            foreach($correction->break_start as $index => $break_start)
            {
                $breakEnd = $correction->break_end[$index];
                $break_id = $correction->break_id[$index];

                if($breakEnd && $break_start) {
                    BreakCorrectionRequest::create([
                        'attendance_correction_id' => $attendance_correction->id,
                        'break_time_id' => $break_id,
                        'requested_break_start' => $break_start,
                        'requested_break_end' => $breakEnd,
                    ]);
                }
            }
        }
        $breakEndCreate = $correction->break_end_create;
        $breakStartCreate = $correction->break_start_create;
        if($breakEndCreate && $breakStartCreate) {
            BreakCorrectionRequest::create([
                'attendance_correction_id' => $attendance_correction->id,
                'break_time_id' => null,
                'requested_break_start' => $breakStartCreate,
                'requested_break_end' => $breakEndCreate,
            ]);
        }

        return $attendance_correction;
    }
}
