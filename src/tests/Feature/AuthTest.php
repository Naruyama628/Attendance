<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // 会員登録
    // 名前が未入力の場合、バリデーションメッセージが表示される
    public function test_register_name_required_validation()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_register_email_required_validation()
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function test_register_password_min_validation()
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => '1234',
            'password_confirmation' => '1234',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される
    public function test_register_password_confirm_validation()
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password321',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_register_password_required_validation()
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'password321',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // フォームに内容が入力されていた場合、データが正常に保存される
    public function test_register_success()
    {
        $response = $this->post('/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
        ]);
    }

    // ログイン
    //メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_email_required_validation()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    //パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_login_password_required_validation()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    //登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_failure()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 管理ログイン
    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_login_email_required_validation()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
     public function test_admin_login_password_required_validation()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
     public function test_admin_login_failure()
    {
        User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 会員登録後、認証メールが送信される
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }
    //メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_user_is_redirected_to_verification_site_when_verification_button_is_clicked()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/email/verify');

        $response->assertStatus(200);

        $response->assertSee('認証はこちらから');

        $response->assertSee(
            'https://mailtrap.io/inboxes',
            false
        );

    }

    // メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
    public function test_user_is_redirected_to_attendance_page_after_email_verification()
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirect('/attendance');

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );

        Event::assertDispatched(Verified::class);
        $response->assertRedirect('/attendance');
    }
}
