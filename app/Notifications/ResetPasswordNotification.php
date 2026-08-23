<?php

namespace App\Notifications;

use App\Support\LemonwaresMail;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return LemonwaresMail::message()
            ->subject(__('account.reset_mail_subject'))
            ->markdown('mail.password-reset', [
                'url' => $url,
                'email' => $notifiable->getEmailForPasswordReset(),
                'expireMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }
}
