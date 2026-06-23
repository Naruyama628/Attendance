<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;

class GeneralUserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //自分が行った勤怠情報が全て表示されている
    public function test_start_work_twice_can_not_button_displayed()
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
            'clock_out' => now(),
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);


        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('06/16(火)');
        $response->assertSee('9:00');
        $response->assertSee('06/17(水)');
        $response->assertSee('9:00');
    }

    //勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_current_month_is_displayed_on_attendance_list()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2026/06');

        Carbon::setTestNow(
            Carbon::create(2026, 5, 17, 9, 0, 0)
        );


        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }

    
    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_is_displayed_when_previous_button_is_clicked()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2026/06');

        $response = $this->get('/attendance/list?month=2026-05');
        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }
    
    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_next_month_is_displayed_when_next_button_is_clicked()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2026/06');

        $response = $this->get('/attendance/list?month=2026-07');
        $response->assertStatus(200);
        $response->assertSee('2026/07');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_attendance_detail_page_is_displayed_when_detail_button_is_clicked()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        // 既に出勤済み
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('09:00');

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('6月16日');
    }

    // 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_user_name_is_displayed_on_attendance_detail_page()
    {
        $user = User::factory()->create([
            'name' => '田中 太郎',
        ]);

        $this->actingAs($user);
        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        // 既に出勤済み
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('田中 太郎');
    }

    // 勤怠詳細画面の「日付」が選択した勤怠の日付になっている
    public function test_attendance_date_is_displayed_on_attendance_detail_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        // 既に出勤済み
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('6月16日');
    }

    // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_clock_in_and_clock_out_times_are_displayed_correctly_on_detail_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_break_times_are_displayed_correctly_on_detail_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-16 10:00'),
            'break_end' => Carbon::parse('2026-06-16 11:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('11:00');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_clock_in_time_is_after_clock_out_time()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-16 10:00'),
            'break_end' => Carbon::parse('2026-06-16 11:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response = $this->post('/request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '19:00',
            'work_end' => '18:00',
            'remarks' => 'テスト',
            'break_start' => ['19:00'],
            'break_end' => ['20:00'],
            'break_id' => [1],
        ]);

        $response->assertSessionHasErrors([
            'work_start' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_break_start_time_is_after_clock_out_time()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-16 10:00'),
            'break_end' => Carbon::parse('2026-06-16 11:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response = $this->post('/request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '09:00',
            'work_end' => '18:00',
            'remarks' => 'テスト',
            'break_start' => ['19:00'],
            'break_end' => ['20:00'],
            'break_id' => [1],
        ]);

        $response->assertSessionHasErrors([
            'break_start.0' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_break_end_time_is_after_clock_out_time()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-16 10:00'),
            'break_end' => Carbon::parse('2026-06-16 11:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response = $this->post('/request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '09:00',
            'work_end' => '18:00',
            'remarks' => 'テスト',
            'break_start' => ['10:00'],
            'break_end' => ['20:00'],
            'break_id' => [1],
        ]);

        $response->assertSessionHasErrors([
            'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_validation_error_is_displayed_when_remarks_is_empty()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-16 10:00'),
            'break_end' => Carbon::parse('2026-06-16 11:00'),
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '09:00',
            'work_end' => '18:00',
            'remarks' => '',
            'break_start' => ['10:00'],
            'break_end' => ['11:00'],
            'break_id' => [1],
        ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    // 修正申請処理が実行される
    public function test_attendance_correction_request_is_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '10:00',
            'work_end' => '19:00',
            'remarks' => 'テスト',
        ]);

        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($adminUser);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('テスト');

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->first();

        $response = $this->get('/admin/stamp_correction_request/approve/' . $correctionRequest->id);
        $response->assertStatus(200);
        $response->assertSee('テスト');
    }

    //「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_own_pending_correction_requests_are_displayed()
    {
        //
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-15',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '10:00',
            'work_end' => '19:00',
            'remarks' => 'テスト1',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '12:00',
            'work_end' => '19:00',
            'remarks' => 'テスト2',
        ]);

        
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('テスト1');
        $response->assertSee('テスト2');
    }

    //「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_all_admin_approved_correction_requests_are_displayed()
    {
        //
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-15',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '10:00',
            'work_end' => '19:00',
            'remarks' => 'テスト1',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '12:00',
            'work_end' => '19:00',
            'remarks' => 'テスト2',
        ]);

        $request = AttendanceCorrectionRequest::where('reason', 'テスト1')->first();

        $request->update([
            'status'  => 'approved',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertDontSee('テスト1');
        $response->assertSee('テスト2');

        $response = $this->get('/stamp_correction_request/list/?type=approved');
        $response->assertStatus(200);
        $response->assertSee('テスト1');
        $response->assertDontSee('テスト2');
    }

    //各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_user_can_view_request_detail()
    {
        //
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-15',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $response = $this->post('request/create', [
            'attendance_id' => $attendance->id,
            'work_start' => '15:00',
            'work_end' => '19:00',
            'remarks' => 'テスト',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response->assertSee('15:00');
        $response->assertDontSee('09:00');
        $response->assertSee('テスト');
    }
}