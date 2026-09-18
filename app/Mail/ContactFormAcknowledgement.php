<?php

namespace App\Mail;

use App\Support\ZeptoMailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormAcknowledgement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullName,
        public string $subjectLine,
    ) {}

    public function envelope(): Envelope
    {
        ZeptoMailSettings::applyRuntimeConfig();

        $fromAddress = ZeptoMailSettings::fromAddress();
        $fromName = ZeptoMailSettings::fromName();

        return new Envelope(
            from: filled($fromAddress)
                ? new Address($fromAddress, $fromName !== '' ? $fromName : null)
                : null,
            subject: __('pages.contact.ack_subject', ['site' => config('site.short_name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-form-acknowledgement',
        );
    }
}
