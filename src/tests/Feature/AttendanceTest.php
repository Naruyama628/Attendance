<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // 現在の日時情報がUIと同じ形式で出力されている
    public function test_current_datetime_is_displayed()
    {
        Carbon::setTestNow(
            Carbon::create(2026, 6, 14, 10, 30, 0)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026年06月14日(日)');
        $response->assertSee('10:30');
    }

    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_off_duty_status_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('勤務外');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_working_status_is_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/attendance/create');

        $response = $this->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('出勤中');
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_break_status_is_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/attendance/create');
        $this->post('/brake_time/create');

        $response = $this->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('休憩中');
    }

    // 退勤済みの場合、勤怠ステータスが正しく表示される
    public function test_after_work_status_is_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/attendance/create');
        $this->post('/attendance/update/work_end');

        $response = $this->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('退勤済');
    }

    //出勤ボタンが正しく機能する
    public function test_clock_in_button()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');

        $response = $this->post('/attendance/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        
        $response->assertSee('出勤中');

        // DB確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);
    }

    //出勤出勤は一日一回のみできる
    public function test_start_work_twice_can_not_button_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('退勤済');

        $response->assertDontSee('出勤');
    }

    //出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('09:00');
    }

    //休憩ボタンが正しく機能する
    public function test_break_button()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('休憩入');

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }
    
    //休憩は一日に何回でもできる
    public function test_break_can_be_taken_multiple_times_in_one_day()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');

        $response = $this->post('/brake_time/update/break_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    //休憩戻ボタンが正しく機能する
    public function test_user_can_end_break()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertDontSee('出勤中');

        $response = $this->post('/brake_time/update/break_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    //休憩戻は一日に何回でもできる
    public function test_user_can_return_from_break_multiple_times_in_a_day()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');

        $response = $this->post('/brake_time/update/break_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    //休憩時刻が勤怠一覧画面で確認できる
    public function test_break_time_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
        ]);

        $response = $this->post('/brake_time/create');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 10, 0, 0)
        );

        $response = $this->post('/brake_time/update/break_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('1:00');
    }

    //退勤ボタンが正しく機能する
    public function test_user_can_clock_out()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤');
        $response->assertDontSee('退勤済');


        $response = $this->post('/attendance/update/work_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    //退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 17, 0, 0)
        );

        // 既に出勤済み
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now()->setTime(9, 0),
        ]);

        $response = $this->post('/attendance/update/work_end');
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance');

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('8:00');
    }
}
