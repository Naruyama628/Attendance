<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BreakTime;
use App\Models\Attendance;

class BreakTimeController extends Controller
{
    //
    public function create(Request $request) {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', today())
            ->first();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakFinished(Request $request) {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', today())
            ->first();

        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest()
            ->first();

        $break->update([
            'break_end' => now(),
        ]);

        return redirect()->route('attendance.create');
    }
}
