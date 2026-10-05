<?php

return [

    'name' => 'LemonWares Technology',
    'short_name' => 'LemonWares',
    'domain' => 'lemonwares.com',
    'url' => env('APP_URL', 'https://lemonwares.com'),
    'tagline' => 'Hosting · Email · Web & Mobile',
    'years_experience' => 12,

    'locales' => [
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
    ],

    'currency' => [
        'primary' => 'USD',
        'secondary' => 'NGN',
        // Fallback only. Live rate is fetched from open.er-api.com and cached hourly.
        'usd_to_ngn' => (float) env('USD_TO_NGN', 7800),
    ],

    'billing_cycles' => [
        'monthly' => [
            'key' => 'monthly',
            'months' => 1,
            'discount_percent' => 0,
            'whmcs' => 'monthly',
        ],
        'quarterly' => [
            'key' => 'quarterly',
            'months' => 3,
            'discount_percent' => 10,
            'whmcs' => 'quarterly',
        ],
        'annually' => [
            'key' => 'annually',
            'months' => 12,
            'discount_percent' => 20,
            'whmcs' => 'annually',
        ],
    ],

    'whmcs' => [
        'base_url' => rtrim(env('WHMCS_BASE_URL', 'https://billing.lemonwares.com'), '/'),
        'client_login_url' => env('WHMCS_CLIENT_LOGIN_URL', 'https://billing.lemonwares.com/clientarea.php'),
        'order_route' => env('WHMCS_ORDER_ROUTE', '/cart.php'),
        'api_identifier' => env('WHMCS_API_IDENTIFIER', ''),
        'api_secret' => env('WHMCS_API_SECRET', ''),
        'api_access_key' => env('WHMCS_API_ACCESS_KEY', ''),
        'payment_method' => env('WHMCS_PAYMENT_METHOD', 'banktransfer'),
        // null = auto (enabled on local only). true/false to force.
        'defer_payment_redirect' => env('WHMCS_DEFER_PAYMENT'),
    ],

    'domain_suggestion_tlds' => [
        'com',
        'net',
        'org',
        'ng',
        'com.ng',
        'io',
        'online',
    ],

    // Fallback WHMCS product ids per hosting group; plans themselves live in the catalog tables (admin).
    'whmcs_pids' => [
        'cpanel' => env('WHMCS_PID_CPANEL', ''),
        'vps' => env('WHMCS_PID_VPS', ''),
    ],

    'hosting_plans' => [],

    'services' => [
        [
            'key' => 'hosting',
            'image' => 'images/services/hosting.jpg',
            'icon' => 'zap',
            'tone' => 'slate',
            'route' => 'home',
            'fragment' => 'hosting-plans',
        ],
        [
            'key' => 'email',
            'image' => 'images/services/email.jpg',
            'icon' => 'mail',
            'tone' => 'blush',
            'route' => 'email.plans',
        ],
        [
            'key' => 'web',
            'image' => 'images/services/web.jpg',
            'icon' => 'code',
            'tone' => 'slate',
            'route' => 'contact',
        ],
        [
            'key' => 'mobile',
            'image' => 'images/services/mobile.jpg',
            'icon' => 'smartphone',
            'tone' => 'rose',
            'route' => 'contact',
        ],
        [
            'key' => 'support',
            'image' => 'images/services/support.jpg',
            'icon' => 'headset',
            'tone' => 'blush',
            'route' => 'contact',
        ],
    ],

    'email' => 'hello@lemonwares.com',
    'contact_form_to' => env('CONTACT_FORM_TO', 'hello@lemonwares.com'),
    'phone' => '+234 906 732 2844',
    'phone_e164' => '+2349067322844',
    'whatsapp' => 'https://wa.me/2349067322844',

    'address' => '26, Akin Leigh Crescent, Lekki Phase 1, Lagos, Nigeria.',

    'social' => [
        ['label' => 'LinkedIn', 'href' => 'https://linkedin.com', 'icon' => 'linkedin'],
        ['label' => 'Facebook', 'href' => 'https://facebook.com', 'icon' => 'facebook'],
        ['label' => 'Instagram', 'href' => 'https://instagram.com', 'icon' => 'instagram'],
    ],

    'technologies' => [
        ['name' => 'JavaScript', 'logo' => 'images/tech/javascript.svg'],
        ['name' => 'TypeScript', 'logo' => 'images/tech/typescript.svg'],
        ['name' => 'Python', 'logo' => 'images/tech/python.svg'],
        ['name' => 'Node.js', 'logo' => 'images/tech/nodejs.svg'],
        ['name' => 'Express', 'logo' => 'images/tech/express.svg'],
        ['name' => 'React', 'logo' => 'images/tech/react.svg'],
        ['name' => 'Next.js', 'logo' => 'images/tech/nextjs.svg'],
        ['name' => 'Vue', 'logo' => 'images/tech/vue.svg'],
        ['name' => 'Vite', 'logo' => 'images/tech/vite.svg'],
        ['name' => 'React Native', 'logo' => 'images/tech/reactnative.svg'],
        ['name' => 'PWA', 'logo' => 'images/tech/pwa.svg'],
        ['name' => 'PHP', 'logo' => 'images/tech/php.svg'],
        ['name' => 'Laravel', 'logo' => 'images/tech/laravel.svg'],
        ['name' => 'WordPress', 'logo' => 'images/tech/wordpress.svg'],
        ['name' => 'PostgreSQL', 'logo' => 'images/tech/postgresql.svg'],
        ['name' => 'MySQL', 'logo' => 'images/tech/mysql.svg'],
        ['name' => 'MongoDB', 'logo' => 'images/tech/mongodb.svg'],
    ],

    'partners' => [
        ['name' => 'Restination Apt', 'meta' => 'Web Development', 'href' => '/case-studies'],
        ['name' => 'Bright Media', 'meta' => 'WordPress Hosting', 'href' => '/case-studies'],
        ['name' => 'Field Service App', 'meta' => 'Mobile Development', 'href' => '/case-studies'],
        ['name' => 'VPS Migration', 'meta' => 'Infrastructure', 'href' => '/case-studies'],
    ],

    'country_options' => [
        'NG' => 'Nigeria',
        'GH' => 'Ghana',
        'KE' => 'Kenya',
        'ZA' => 'South Africa',
        'EG' => 'Egypt',
        'MA' => 'Morocco',
        'RW' => 'Rwanda',
        'TZ' => 'Tanzania',
        'UG' => 'Uganda',
        'CM' => 'Cameroon',
        'CI' => "Cote d'Ivoire",
        'SN' => 'Senegal',
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
        'BR' => 'Brazil',
        'AR' => 'Argentina',
        'GB' => 'United Kingdom',
        'IE' => 'Ireland',
        'FR' => 'France',
        'DE' => 'Germany',
        'NL' => 'Netherlands',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'PT' => 'Portugal',
        'SE' => 'Sweden',
        'NO' => 'Norway',
        'DK' => 'Denmark',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'QA' => 'Qatar',
        'IN' => 'India',
        'PK' => 'Pakistan',
        'BD' => 'Bangladesh',
        'CN' => 'China',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'SG' => 'Singapore',
        'MY' => 'Malaysia',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
    ],

];
