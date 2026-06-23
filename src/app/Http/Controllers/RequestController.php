<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;
use App\Http\Requests\CorrectionRequest;

class RequestController extends Controller
{
    public function index(Request $request) {

        if ($request->attributes->get('is_admin')) {
            return app(AdminController::class)->RequestIndex($request);
        }

        if($request->type === 'approved'){
            $attendanceCorrections = AttendanceCorrectionRequest::where
            ('user_id', Auth()->user()->id)
            ->where('status', 'approved')
            ->get();
            $tab = 'approved';
        } else {
            $attendanceCorrections = AttendanceCorrectionRequest::where
            ('user_id', Auth()->user()->id)
            ->where('status', 'pending')
            ->get();
            $tab = 'pending';
        }

        $statusMessage = [
            'pending' => '承認待ち',
            'approved' => '承認済み'
            ];

        return view('requests.index', compact('attendanceCorrections', 'statusMessage', 'tab'));
    }

    //
    public function create(CorrectionRequest $request) {
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
        $this->requestCreate($correction);
        
        return redirect()->route('attendance.detail', $request->attendance_id);
    }

    // 共通処理
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
    }
}
