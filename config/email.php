<?php

return [

    'webmail_url' => env('TREKMAIL_WEBMAIL_URL', 'https://trekmail.net/webmail'),

    'product_name' => 'Lemon Mail',

    /*
    |--------------------------------------------------------------------------
    | Lemon Mail DNS template (TrekMail)
    |--------------------------------------------------------------------------
    | Used for the customer checklist and Cloudflare one-click apply.
    | Update values if TrekMail changes their MX / SPF / DKIM hosts.
    */
    'dns_template' => [
        [
            'type' => 'MX',
            'name' => '@',
            'value' => 'mail.trekmail.net',
            'priority' => 10,
        ],
        [
            'type' => 'TXT',
            'name' => '@',
            'value' => 'v=spf1 include:_spf.trekmail.net ~all',
            'priority' => null,
        ],
        [
            'type' => 'TXT',
            'name' => '_dmarc',
            'value' => 'v=DMARC1; p=none;',
            'priority' => null,
        ],
    ],

    'billing_cycles' => [
        [
            'key' => 'monthly',
            'months' => 1,
            'discount_percent' => 0,
        ],
        [
            'key' => 'quarterly',
            'months' => 3,
            'discount_percent' => 10,
        ],
        [
            'key' => 'semiannual',
            'months' => 6,
            'discount_percent' => 15,
        ],
        [
            'key' => 'annually',
            'months' => 12,
            'discount_percent' => 22,
        ],
    ],

    'plans' => [
        [
            'key' => 'solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 1,
            'monthly_usd' => 4.99,
            'featured' => false,
        ],
        [
            'key' => 'team',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 5,
            'monthly_usd' => 19.99,
            'featured' => true,
        ],
        [
            'key' => 'business',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 10,
            'monthly_usd' => 34.99,
            'featured' => false,
        ],
        [
            'key' => 'scale',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 25,
            'monthly_usd' => 59.99,
            'featured' => false,
        ],
        [
            'key' => 'titan_business',
            'provider' => 'titan',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 5,
            'monthly_usd' => 14.99,
            'featured' => false,
        ],
        [
            'key' => 'google_workspace_business_starter',
            'provider' => 'google_workspace',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 5,
            'monthly_usd' => 30.00,
            'featured' => false,
        ],
        [
            'key' => 'microsoft_365_business_basic',
            'provider' => 'ms365',
            'fulfilment_mode' => 'manual',
            'mailboxes' => 5,
            'monthly_usd' => 27.50,
            'featured' => false,
        ],
    ],

    'enterprise_products' => [
        [
            'key' => 'google_workspace',
            'name' => 'Google Workspace',
        ],
        [
            'key' => 'microsoft_365',
            'name' => 'Microsoft 365',
        ],
        [
            'key' => 'titan',
            'name' => 'Titan Business Email',
        ],
    ],

    'default_local_parts' => [
        'hello',
        'info',
        'sales',
        'support',
        'admin',
        'accounts',
        'billing',
        'contact',
        'ops',
        'team',
    ],

];
