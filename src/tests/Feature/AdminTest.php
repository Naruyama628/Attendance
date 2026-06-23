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
use App\Models\BreakCorrectionRequest;

class AdminTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_daily_attendance_of_all_users_is_displayed_correctly()
    {
        $user1 = User::factory()->create([
            'name' => '太郎',
        ]);

        $this->actingAs($user1);

        $attendance = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 09:00'),
            'clock_out' => Carbon::parse('2026-06-16 18:00'),
        ]);

        $user2 = User::factory()->create([
            'name' => '花子',
        ]);

        $this->actingAs($user2);

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-06-16',
            'clock_in' => Carbon::parse('2026-06-16 10:00'),
            'clock_out' => Carbon::parse('2026-06-16 20:00'),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/list?month=2026-06-16');
        $response->assertStatus(200);
        $response->assertSee('花子');
        $response->assertSee('太郎');
    }

    // 遷移した際に現在の日付が表示される
    public function test_current_date_is_displayed_on_admin_attendance_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $this->actingAs($admin);
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('2026/06/16');
    }

    //「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_previous_day_is_displayed_when_previous_day_button_is_clicked()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-15',
            'clock_in' => Carbon::parse('2026-06-15 10:00'),
            'clock_out' => Carbon::parse('2026-06-15 20:00'),
        ]);

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertDontSee('太郎');

        $response = $this->get('/admin/attendance/list?month=2026-06-15');
        $response->assertStatus(200);
        $response->assertSee('太郎');
    }
    

    // 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_next_day_is_displayed_when_next_day_button_is_clicked()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertDontSee('太郎');

        $response = $this->get('/admin/attendance/list?month=2026-06-17');
        $response->assertStatus(200);
        $response->assertSee('太郎');
    }

    // 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_attendance_detail_page_displays_selected_attendance_record()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('太郎');
        $response->assertSee('6月17日');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_clock_in_time_is_after_clock_out_time()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/admin/correction/createApproval', [
            'attendance_id' => $attendance->id,
            'work_start' => '19:00',
            'work_end' => '18:00',
            'remarks' => 'テスト',
        ]);
        
        $response->assertSessionHasErrors([
            'work_start' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_break_start_time_is_after_clock_out_time()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/admin/correction/createApproval', [
            'attendance_id' => $attendance->id,
            'work_start' => '17:00',
            'work_end' => '18:00',
            'remarks' => 'テスト',
            'break_start' => ['19:00'],
            'break_end' => ['20:00'],
            'break_id' => [$break->id],
        ]);
        
        $response->assertSessionHasErrors([
            'break_start.0' => '休憩時間が不適切な値です',
        ]);
    }
    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_error_message_is_displayed_when_break_end_time_is_after_clock_out_time()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/admin/correction/createApproval', [
            'attendance_id' => $attendance->id,
            'work_start' => '10:00',
            'work_end' => '20:00',
            'remarks' => 'テスト',
            'break_start' => ['11:00'],
            'break_end' => ['21:00'],
            'break_id' => [$break->id],
        ]);
        
        $response->assertSessionHasErrors([
            'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }
    // 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_error_message_is_displayed_when_remarks_is_empty()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);

        $response = $this->post('/admin/correction/createApproval', [
            'attendance_id' => $attendance->id,
            'work_start' => '10:00',
            'work_end' => '20:00',
            'remarks' => '',
            'break_start' => ['11:00'],
            'break_end' => ['12:00'],
            'break_id' => [$break->id],
        ]);
        
        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_all_users_names_and_email_addresses()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com'
        ]);

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);
        $response->assertSee('taro@example.com');
    }

    // ユーザーの勤怠情報が正しく表示される
    public function test_user_attendance_information_is_displayed_correctly()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com'
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/staff/' . $user->id);
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('20:00');
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_is_displayed_when_previous_month_button_is_clicked()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com'
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-17',
            'clock_in' => Carbon::parse('2026-05-17 10:00'),
            'clock_out' => Carbon::parse('2026-05-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-05-17 11:00'),
            'break_end' => Carbon::parse('2026-05-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/staff/' . $user->id);
        $response->assertStatus(200);
        $response->assertDontSee('10:00');
        $response->assertDontSee('20:00');

        $response = $this->get('/admin/attendance/staff/' . $user->id . 'month=2026-05');
        $response->assertStatus(200);
        $response->assertDontSee('10:00');
        $response->assertDontSee('20:00');
    }
    // 「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_next_month_is_displayed_when_next_month_button_is_clicked()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com'
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 16, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-17',
            'clock_in' => Carbon::parse('2026-07-17 10:00'),
            'clock_out' => Carbon::parse('2026-07-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-07-17 11:00'),
            'break_end' => Carbon::parse('2026-07-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/staff/' . $user->id);
        $response->assertStatus(200);
        $response->assertDontSee('10:00');
        $response->assertDontSee('20:00');

        $response = $this->get('/admin/attendance/staff/' . $user->id . 'month=2026-07');
        $response->assertStatus(200);
        $response->assertDontSee('10:00');
        $response->assertDontSee('20:00');
    }
    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_attendance_detail_page_is_displayed_when_detail_button_is_clicked()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'taro@example.com'
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $response = $this->get('/admin/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('6月17日');
        $response->assertSee('10:00');
        $response->assertSee('20:00');
        $response->assertSee('11:00');
        $response->assertSee('12:00');
    }
    // 承認待ちの修正申請が全て表示されている
    public function test_admin_can_view_all_pending_correction_requests()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user1 = User::factory()->create([
            'name' => '太郎',
        ]);

        $user2 = User::factory()->create([
            'name' => '次郎',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user1->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 11:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 11:00'),
            'reason' => 'テスト',
            'status' => 'pending'
        ]);

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user2->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 11:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 11:00'),
            'reason' => 'test',
            'status' => 'pending'
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('太郎');
        $response->assertSee('次郎');
    }
    // 承認済みの修正申請が全て表示されている
    public function test_admin_can_view_all_approved_correction_requests()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user1 = User::factory()->create([
            'name' => '太郎',
        ]);

        $user2 = User::factory()->create([
            'name' => '次郎',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user1->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 11:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 11:00'),
            'reason' => 'テスト',
            'status' => 'pending'
        ]);

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user2->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 11:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 11:00'),
            'reason' => 'test',
            'status' => 'approved'
        ]);

        $response = $this->get('/stamp_correction_request/list/?type=approved');
        $response->assertStatus(200);
        $response->assertDontSee('太郎');
        $response->assertSee('次郎');
    }
    // 修正申請の詳細内容が正しく表示されている
    public function test_admin_can_view_correction_request_details()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 15:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 16:00'),
            'reason' => 'テスト',
            'status' => 'pending'
        ]);

        BreakCorrectionRequest::create([
            'attendance_correction_id' => $correction->id,
            'break_time_id' => $break->id,
            'requested_break_start' => Carbon::parse('2026-06-17 11:00'),
            'requested_break_end' => Carbon::parse('2026-06-17 13:00'),
        ]);

        $response = $this->get('/admin/stamp_correction_request/approve/' . $correction->id);
        $response->assertStatus(200);
        $response->assertSee('太郎');
        $response->assertSee('テスト');
        $response->assertSee('15:00');
        $response->assertDontSee('10:00');
        $response->assertSee('13:00');
        $response->assertDontSee('12:00');
    }
    // 修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_correction_request()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => '太郎',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 6, 17, 9, 0, 0)
        );

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-17',
            'clock_in' => Carbon::parse('2026-06-17 10:00'),
            'clock_out' => Carbon::parse('2026-06-17 20:00'),
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::parse('2026-06-17 11:00'),
            'break_end' => Carbon::parse('2026-06-17 12:00'),
        ]);

        $correction = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => Carbon::parse('2026-06-17 11:00'),
            'requested_clock_out' => Carbon::parse('2026-06-17 16:00'),
            'reason' => 'テスト',
            'status' => 'pending'
        ]);

        BreakCorrectionRequest::create([
            'attendance_correction_id' => $correction->id,
            'break_time_id' => $break->id,
            'requested_break_start' => Carbon::parse('2026-06-17 11:00'),
            'requested_break_end' => Carbon::parse('2026-06-17 13:00'),
        ]);

        $response = $this->post('/admin/correction/approval', [
            'attendance_id' => $attendance->id,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertDontSee('10:00');
        $response->assertDontSee('20:00');

        $response->assertSee('11:00');
        $response->assertSee('16:00');

        $response->assertDontSee('12:00');
        $response->assertSee('13:00');
    }
}
