<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_and_reset_password_pages_render(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(__('account.forgot_title'), false);

        $this->get(route('password.reset', ['token' => 'demo-token', 'email' => 'ada@example.com']))
            ->assertOk()
            ->assertSee(__('account.reset_title'), false);
    }

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'ada@example.com',
        ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ada@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_mail_uses_zeptomail_from_and_logo(): void
    {
        config([
            'services.zeptomail.from_address' => 'noreply@lemonwares.com',
            'services.zeptomail.from_name' => 'LemonWares',
            'app.url' => 'https://gadgets.lemonwares.com',
        ]);

        $user = User::factory()->create(['email' => 'ada@example.com']);
        $notification = new ResetPasswordNotification('demo-token');
        $mail = $notification->toMail($user);

        $this->assertSame(['noreply@lemonwares.com', 'LemonWares'], $mail->from);
        $this->assertStringContainsString('lemonwareslogo', $mail->render());
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'old-password',
        ]);

        $token = $this->passwordBroker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_reset_rejects_stale_token_after_newer_request(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'old-password',
        ]);

        $broker = $this->passwordBroker();
        $staleToken = $broker->createToken($user);
        $freshToken = $broker->createToken($user);

        $this->post(route('password.update'), [
            'token' => $staleToken,
            'email' => 'ada@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('password.reset', [
            'token' => $staleToken,
            'email' => 'ada@example.com',
        ]))
            ->assertSessionHasErrors('email');

        $this->post(route('password.update'), [
            'token' => $freshToken,
            'email' => 'ADA@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_zeptomail_transport_posts_to_api(): void
    {
        config([
            'services.zeptomail.token' => 'zm_test_token',
            'services.zeptomail.endpoint' => 'https://api.zeptomail.test/v1.1/email',
        ]);

        Http::fake([
            'https://api.zeptomail.test/v1.1/email' => Http::response([
                'message' => 'OK',
                'request_id' => 'req-1',
            ], 200),
        ]);

        Mail::mailer('zeptomail')->raw('Hello from LemonWares', function ($message): void {
            $message->to('ada@example.com')
                ->from('mails@lemonwares.com', 'LemonWares')
                ->subject('Test');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.zeptomail.test/v1.1/email'
                && $request->hasHeader('Authorization', 'Zoho-enczapikey zm_test_token')
                && data_get($request->data(), 'from.address') === 'mails@lemonwares.com'
                && data_get($request->data(), 'to.0.email_address.address') === 'ada@example.com'
                && data_get($request->data(), 'subject') === 'Test';
        });
    }

    public function test_zeptomail_embeds_branded_logo_as_inline_image(): void
    {
        config([
            'services.zeptomail.token' => 'zm_test_token',
            'services.zeptomail.endpoint' => 'https://api.zeptomail.test/v1.1/email',
            'services.zeptomail.logo_url' => 'https://gadgets.lemonwares.com/lemonwareslogo.png',
        ]);

        Http::fake([
            'https://api.zeptomail.test/v1.1/email' => Http::response([
                'message' => 'OK',
                'request_id' => 'req-logo',
            ], 200),
        ]);

        $html = '<html><body><img src="https://gadgets.lemonwares.com/lemonwareslogo.png" alt="LemonWares"></body></html>';

        Mail::mailer('zeptomail')->html($html, function ($message): void {
            $message->to('ada@example.com')
                ->from('mails@lemonwares.com', 'LemonWares')
                ->subject('Branded');
        });

        Http::assertSent(function ($request) {
            $htmlBody = (string) data_get($request->data(), 'htmlbody');
            $inline = data_get($request->data(), 'inline_images.0');

            return $request->url() === 'https://api.zeptomail.test/v1.1/email'
                && str_contains($htmlBody, 'cid:lemonwares-logo')
                && ! str_contains($htmlBody, 'gadgets.lemonwares.com/lemonwareslogo.png')
                && data_get($inline, 'cid') === 'lemonwares-logo'
                && data_get($inline, 'mime_type') === 'image/png'
                && filled(data_get($inline, 'content'));
        });
    }

    public function test_zeptomail_strips_authorization_prefix_from_token(): void
    {
        config([
            'services.zeptomail.token' => 'Zoho-enczapikey zm_prefixed_token',
            'services.zeptomail.endpoint' => 'https://api.zeptomail.test/v1.1/email',
        ]);

        Http::fake([
            'https://api.zeptomail.test/v1.1/email' => Http::response(['message' => 'OK'], 200),
        ]);

        Mail::mailer('zeptomail')->raw('Hello', function ($message): void {
            $message->to('ada@example.com')
                ->from('mails@lemonwares.com', 'LemonWares')
                ->subject('Test');
        });

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Zoho-enczapikey zm_prefixed_token');
        });
    }

    private function passwordBroker(): PasswordBroker
    {
        $broker = Password::broker();

        $this->assertInstanceOf(PasswordBroker::class, $broker);

        return $broker;
    }
}
