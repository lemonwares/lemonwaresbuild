<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin panel permissions
    |--------------------------------------------------------------------------
    |
    | Keys are stored on staff users (admin_permissions JSON). Super admins
    | bypass these checks. Route-name prefixes map to a permission key.
    |
    */

    'permissions' => [
        'dashboard' => 'Dashboard',
        'customers' => 'Customers',
        'staff' => 'Staff & permissions',
        'email_orders' => 'Email orders',
        'hosting_leads' => 'Hosting leads',
        'support' => 'Support tickets',
        'subscribers' => 'Subscribers',
        'campaigns' => 'Newsletter campaigns',
        'hosting_prices' => 'Hosting prices',
        'email_catalog' => 'Email & suite pricing',
        'email_providers' => 'Email provider settings',
        'whmcs' => 'WHMCS settings',
        'flutterwave' => 'Flutterwave settings',
        'zeptomail' => 'ZeptoMail settings',
        'cloudflare' => 'Cloudflare settings',
        'blog' => 'Blog',
        'projects' => 'Projects',
        'team' => 'Team members',
        'careers' => 'Careers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route name → permission
    |--------------------------------------------------------------------------
    */

    'route_permissions' => [
        'admin.dashboard' => 'dashboard',
        'admin.search' => 'dashboard',
        'admin.customers' => 'customers',
        'admin.staff' => 'staff',
        'admin.email-orders' => 'email_orders',
        'admin.hosting-leads' => 'hosting_leads',
        'admin.support-tickets' => 'support',
        'admin.subscribers' => 'subscribers',
        'admin.newsletter-campaigns' => 'campaigns',
        'admin.hosting-prices' => 'hosting_prices',
        'admin.email-catalog' => 'email_catalog',
        'admin.email-provider-settings' => 'email_providers',
        'admin.whmcs-settings' => 'whmcs',
        'admin.flutterwave-settings' => 'flutterwave',
        'admin.zeptomail-settings' => 'zeptomail',
        'admin.cloudflare-settings' => 'cloudflare',
        'admin.blog-posts' => 'blog',
        'admin.projects' => 'projects',
        'admin.team-members' => 'team',
        'admin.career-openings' => 'careers',
    ],

];
