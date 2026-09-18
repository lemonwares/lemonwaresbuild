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

class SupportTicketAcknowledgement extends Mailable
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
            subject: __('pages.support_page.ack_subject', ['reference' => $this->ticket->reference]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support-ticket-acknowledgement',
        );
    }
}
