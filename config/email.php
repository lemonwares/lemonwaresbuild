<?php

return [

    'webmail_url' => env('TREKMAIL_WEBMAIL_URL', 'https://trekmail.net/webmail'),

    'product_name' => 'Lemon Mail',

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
            'fulfilment_mode' => 'auto',
            'mailboxes' => 1,
            'monthly_usd' => 4.99,
            'featured' => false,
        ],
        [
            'key' => 'team',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'mailboxes' => 5,
            'monthly_usd' => 19.99,
            'featured' => true,
        ],
        [
            'key' => 'business',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'mailboxes' => 10,
            'monthly_usd' => 34.99,
            'featured' => false,
        ],
        [
            'key' => 'scale',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
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
