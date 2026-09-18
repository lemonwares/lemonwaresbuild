<?php

return [

    'permissions' => [
        'overview' => 'Overview',
        'products' => 'Products',
        'subscriptions' => 'Subscriptions',
        'invoices' => 'Invoices',
        'activity' => 'Activity',
        'profile' => 'Profile',
        'staff' => 'Staff',
        'settings' => 'Settings',
        'notifications' => 'Notifications',
    ],

    'route_permissions' => [
        'account.show' => 'overview',
        'account.products' => 'products',
        'account.email' => 'products',
        'account.vps' => 'products',
        'account.hosting' => 'products',
        'account.domains' => 'products',
        'account.subscriptions' => 'subscriptions',
        'account.invoices' => 'invoices',
        'account.activity' => 'activity',
        'account.profile' => 'profile',
        'account.staff' => 'staff',
        'account.settings' => 'settings',
        'account.contacts' => 'settings',
        'account.notifications' => 'notifications',
        'email.pay' => 'products',
        'email.renew' => 'products',
        'email.provision' => 'products',
    ],

];
