<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Support\ZeptoMailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        ZeptoMailSettings::applyRuntimeConfig();

        $fromAddress = ZeptoMailSettings::fromAddress();
        $fromName = ZeptoMailSettings::fromName();

        return new Envelope(
            from: filled($fromAddress)
                ? new Address($fromAddress, $fromName !== '' ? $fromName : null)
                : null,
            replyTo: [
                new Address($this->ticket->email, $this->ticket->full_name),
            ],
            subject: config('site.short_name').' · Ticket '.$this->ticket->reference.': '.$this->ticket->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support-ticket-submitted',
        );
    }
}
