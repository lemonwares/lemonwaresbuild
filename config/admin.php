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
        'reports' => 'Reports & sales export',
        'customers' => 'Customers',
        'impersonate' => 'Log in as a customer',
        'staff' => 'Staff, roles & permissions',
        'audit_log' => 'Audit log',
        'orders' => 'Orders (domain, cart, all orders)',
        'email_orders' => 'Email orders',
        'hosting_leads' => 'Hosting leads',
        'support' => 'Support tickets',
        'subscribers' => 'Subscribers',
        'campaigns' => 'Newsletter campaigns',
        'hosting_prices' => 'Plans & pricing',
        'coupons' => 'Discount codes',
        'email_catalog' => 'Email & suite pricing',
        'email_providers' => 'Email provider settings',
        'whmcs' => 'WHMCS settings & console',
        'flutterwave' => 'Flutterwave settings',
        'zeptomail' => 'ZeptoMail settings',
        'cloudflare' => 'Cloudflare settings',
        'cloudinary' => 'Cloudinary settings',
        'hetzner' => 'Hetzner (VPS) settings',
        'site_settings' => 'Site settings & maintenance mode',
        'system' => 'System tools, jobs, logs & backups',
        'content' => 'Website content, FAQ, legal & emails',
        'blog' => 'Blog',
        'projects' => 'Projects',
        'case_studies' => 'Case studies',
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
        'admin.reports' => 'reports',
        'admin.customers.impersonate' => 'impersonate',
        'admin.impersonation' => 'customers',
        'admin.customers' => 'customers',
        'admin.staff' => 'staff',
        'admin.roles' => 'staff',
        'admin.audit-log' => 'audit_log',
        'admin.orders' => 'orders',
        'admin.domain-orders' => 'orders',
        'admin.cart-orders' => 'orders',
        'admin.email-orders' => 'email_orders',
        'admin.hosting-leads' => 'hosting_leads',
        'admin.support-tickets' => 'support',
        'admin.saved-replies' => 'support',
        'admin.subscribers' => 'subscribers',
        'admin.newsletter-campaigns' => 'campaigns',
        'admin.catalog' => 'hosting_prices',
        'admin.coupons' => 'coupons',
        'admin.email-catalog' => 'email_catalog',
        'admin.email-provider-settings' => 'email_providers',
        'admin.whmcs-settings' => 'whmcs',
        'admin.whmcs-console' => 'whmcs',
        'admin.flutterwave-settings' => 'flutterwave',
        'admin.zeptomail-settings' => 'zeptomail',
        'admin.cloudflare-settings' => 'cloudflare',
        'admin.cloudinary-settings' => 'cloudinary',
        'admin.hetzner-settings' => 'hetzner',
        'admin.site-settings' => 'site_settings',
        'admin.system' => 'system',
        'admin.content' => 'content',
        'admin.blog-posts' => 'blog',
        'admin.projects' => 'projects',
        'admin.case-studies' => 'case_studies',
        'admin.team-members' => 'team',
        'admin.career-openings' => 'careers',
    ],

];
