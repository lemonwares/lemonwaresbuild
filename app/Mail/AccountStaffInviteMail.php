<?php

namespace App\Mail;

use App\Models\AccountInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountStaffInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AccountInvite $invite) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('account.staff_invite_mail_subject', ['company' => config('site.short_name')]),
        );
    }

    public function content(): Content
    {
        $invite = $this->invite->loadMissing('owner');

        return new Content(
            markdown: 'mail.account-staff-invite',
            with: [
                'invite' => $invite,
                'ownerName' => $invite->owner?->name ?? config('site.short_name'),
                'acceptUrl' => route('account.invites.show', $invite->token),
            ],
        );
    }
}
