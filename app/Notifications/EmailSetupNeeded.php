<?php

namespace App\Notifications;

use App\Models\EmailOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailSetupNeeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmailOrder $order, public bool $expired = false) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $title = ($this->expired ? 'Business email expired, suspend it: ' : 'Business email setup needed: ').$order->domain;

        return \App\Support\LemonWaresMail::message()
            ->subject($title)
            ->markdown('mail.account-notification', [
                'title' => $title,
                'body' => $this->expired ? sprintf(
                    '%s for %s (%s) was not renewed and has expired. Suspend or delete it in the provider portal.',
                    $order->plan_name ?: $order->plan_key,
                    $order->domain,
                    $order->user?->email ?: 'no email',
                ) : sprintf(
                    '%s (%s) paid for %s on %s, %d mailbox(es). Create the mailboxes, then send the login details from the admin order page.',
                    $order->user?->name ?: 'A customer',
                    $order->user?->email ?: 'no email',
                    $order->plan_name ?: $order->plan_key,
                    $order->domain,
                    (int) $order->mailbox_count,
                ),
                'url' => route('admin.email-orders.show', $order),
                'action' => 'Open order',
            ]);
    }
}
