<?php

namespace App\Notifications;

use App\Models\EmailOrder;
use App\Support\LemonwaresMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailboxCredentialsNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<array{address:string,password:string}>  $mailboxes
     */
    public function __construct(
        public EmailOrder $order,
        public string $webmailUrl,
        public array $mailboxes,
        public ?string $note = null,
    ) {}

    /**
     * Always email credentials to the account login address.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (method_exists($notifiable, 'wantsInAppNotifications') && $notifiable->wantsInAppNotifications()) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return LemonwaresMail::message()
            ->subject(__('email.credentials_mail_subject', ['domain' => $this->order->domain]))
            ->markdown('mail.mailbox-credentials', [
                'domain' => $this->order->domain,
                'webmailUrl' => $this->webmailUrl,
                'mailboxes' => $this->mailboxes,
                'note' => $this->note,
                'orderUrl' => route('account.email.show', $this->order),
            ]);
    }

    /**
     * @return array{title:string,body:string,url:string,product:string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('account.notif_email_provisioned_title'),
            'body' => __('email.credentials_mail_in_app', ['domain' => $this->order->domain]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
