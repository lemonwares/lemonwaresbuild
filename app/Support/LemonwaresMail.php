<?php

namespace App\Support;

use App\Support\ZeptoMailSettings;
use Illuminate\Notifications\Messages\MailMessage;

class LemonwaresMail
{
    public static function message(): MailMessage
    {
        ZeptoMailSettings::applyRuntimeConfig();

        $message = new MailMessage;

        $from = ZeptoMailSettings::fromAddress();
        $name = ZeptoMailSettings::fromName();

        if (filled($from)) {
            $message->from($from, $name !== '' ? $name : null);
        }

        return $message;
    }
}
