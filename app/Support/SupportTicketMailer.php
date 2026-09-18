<?php

namespace App\Support;

use App\Mail\SupportTicketAcknowledgement;
use App\Mail\SupportTicketSubmitted;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Mail;

class SupportTicketMailer
{
    public static function send(SupportTicket $ticket): void
    {
        ZeptoMailSettings::applyRuntimeConfig();

        $mailer = ZeptoMailSettings::isConfigured()
            ? 'zeptomail'
            : (string) config('mail.default', 'log');

        $recipient = ContactFormSettings::inboxAddress();

        Mail::mailer($mailer)->to($recipient)->send(new SupportTicketSubmitted($ticket));

        Mail::mailer($mailer)->to($ticket->email)->send(new SupportTicketAcknowledgement($ticket));
    }
}
