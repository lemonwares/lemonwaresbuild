<?php

namespace App\Support;

use App\Mail\ContactFormAcknowledgement;
use App\Mail\ContactFormSubmitted;
use Illuminate\Support\Facades\Mail;

class ContactFormMailer
{
    /**
     * @param  array{full_name:string,email:string,subject:string,message:string}  $data
     */
    public static function send(array $data): void
    {
        ZeptoMailSettings::applyRuntimeConfig();

        $mailer = ZeptoMailSettings::isConfigured()
            ? 'zeptomail'
            : (string) config('mail.default', 'log');

        $recipient = ContactFormSettings::inboxAddress();

        Mail::mailer($mailer)->to($recipient)->send(new ContactFormSubmitted(
            fullName: $data['full_name'],
            email: $data['email'],
            subjectLine: $data['subject'],
            body: $data['message'],
        ));

        Mail::mailer($mailer)->to($data['email'])->send(new ContactFormAcknowledgement(
            fullName: $data['full_name'],
            subjectLine: $data['subject'],
        ));
    }
}
