<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;

class RequestController extends Controller
{
    public function index(Request $request) {
        if($request->type === 'approved'){
            $attendanceCorrections = AttendanceCorrectionRequest::where('status', 'approved')->get();
            $tab = 'approved';
        } else {
            $attendanceCorrections = AttendanceCorrectionRequest::where('status', 'pending')->get();
            $tab = 'pending';
        }

        $statusMessage = [
            'pending' => '承認待ち',
            'approved' => '承認済み'
            ];

        return view('requests.index', compact('attendanceCorrections', 'statusMessage', 'tab'));
    }

    //
    public function create(Request $request) {
        $attendance = Attendance::find($request->attendance_id);
        $work_start = \Carbon\Carbon::parse($attendance->clock_in->format('Y-m-d') . ' ' . $request->work_start);

        $work_end = \Carbon\Carbon::parse($attendance->clock_in->format('Y-m-d') . ' ' . $request->work_end);

        $attendance_correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $request->attendance_id,
            'user_id' => auth()->id(),
            'requested_clock_in' => $work_start,
            'requested_clock_out' => $work_end,
            'reason' => $request->remarks,
        ]);

        $index = 0;
        if($request->break_start) {
            foreach($request->break_start as $index => $break_start)
            {
                $breakEnd = $request->break_end[$index];
                $break_id = $request->break_id[$index];

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
        $breakEndCreate = $request->break_end_create;
        $breakStartCreate = $request->break_start_create;
        if($breakEndCreate && $breakStartCreate) {
            BreakCorrectionRequest::create([
                'attendance_correction_id' => $attendance_correction->id,
                'break_time_id' => null,
                'requested_break_start' => $breakStartCreate,
                'requested_break_end' => $breakEndCreate,
            ]);
        }
        
        return redirect()->route('attendance.detail', $request->attendance_id);
    }
}
