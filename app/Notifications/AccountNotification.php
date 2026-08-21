<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class AccountNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        $channels = [];

        if ($notifiable->wantsInAppNotifications()) {
            $channels[] = 'database';
        }

        if ($notifiable->wantsEmailNotifications()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();

        return (new MailMessage)
            ->subject($payload['title'])
            ->markdown('mail.account-notification', [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'url' => $payload['url'],
                'action' => $payload['action'] ?? __('account.notifications_open'),
            ]);
    }

    /**
     * @return array{title:string,body:string,url:string,product:string,action?:string}
     */
    public function toArray(object $notifiable): array
    {
        $payload = $this->payload();

        return [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'url' => $payload['url'],
            'product' => $payload['product'],
        ];
    }

    /**
     * @return array{title:string,body:string,url:string,product:string,action?:string}
     */
    abstract protected function payload(): array;
}
