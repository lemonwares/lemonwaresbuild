<?php

namespace App\Notifications;

use App\Support\LemonWaresMail;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return LemonWaresMail::message()
            ->subject(__('account.verify_mail_subject'))
            ->markdown('mail.verify-email', [
                'url' => $this->verificationUrl($notifiable),
                'expireMinutes' => (int) config('auth.verification.expire', 60),
            ]);
    }
}
