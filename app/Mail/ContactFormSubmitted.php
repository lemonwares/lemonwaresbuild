<?php

namespace App\Mail;

use App\Support\ZeptoMailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullName,
        public string $email,
        public string $subjectLine,
        public string $body,
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
            replyTo: [
                new Address($this->email, $this->fullName),
            ],
            subject: config('site.short_name').' · Contact: '.$this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-form-submitted',
        );
    }
}
