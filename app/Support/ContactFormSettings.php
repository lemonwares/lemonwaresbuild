<?php

namespace App\Support;

use App\Models\IntegrationSetting;

class ContactFormSettings
{
    public static function inboxAddress(): string
    {
        return (string) IntegrationSetting::getValue(
            'contact_form.inbox',
            (string) config('site.contact_form_to', config('site.email')),
        );
    }
}
