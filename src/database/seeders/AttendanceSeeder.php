<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $user1 = User::where('email', 'user1@example.com')->first();
        $start = Carbon::now()->subMonths(2)->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        foreach (CarbonPeriod::create($start, $end) as $date) {
            // 土日は作らない場合
            if ($date->isWeekend()) {
                continue;
            }

            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $user1->id,
                    'work_date' => $date->toDateString(),
                ],
                [
                    'clock_in' => $date->copy()->setTime(9, 0),
                    'clock_out' => $date->copy()->setTime(18, 0),
                ]
            );

            BreakTime::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'break_start' => $date->copy()->setTime(12, 0),
                ],
                [
                    'break_end' => $date->copy()->setTime(13, 0),
                ]
            );
        }

        $user2 = User::where('email', 'user2@example.com')->first();

        $date = Carbon::now();

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $date->copy(),
            'clock_in' => $date->copy()->setTime(9, 0),
            'clock_out' => $date->copy()->setTime(18, 0),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $date->copy()->subDay(),
            'clock_in' => $date->copy()->subDay()->setTime(9, 0),
            'clock_out' => $date->copy()->subDay()->setTime(18, 0),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->subDay()->setTime(12, 0),
            'break_end' => $date->copy()->subDay()->setTime(13, 0),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->subDay()->setTime(15, 0),
            'break_end' => $date->copy()->subDay()->setTime(16, 0),
        ]);
    }
}
