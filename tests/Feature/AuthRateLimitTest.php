<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_per_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'secretpass',
        ]);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => 'ada@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many sign-in attempts',
            (string) session('errors')->first('email'),
        );
    }

    public function test_forgot_password_is_rate_limited_per_email_and_ip(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'grace@example.com',
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->from(route('password.request'))->post(route('password.email'), [
                'email' => 'grace@example.com',
            ])->assertSessionDoesntHaveErrors('email');
        }

        Notification::assertSentTo(
            User::query()->where('email', 'grace@example.com')->first(),
            ResetPassword::class,
        );

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'grace@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many reset requests',
            (string) session('errors')->first('email'),
        );
    }
}
