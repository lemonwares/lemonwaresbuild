<?php
/**
 * LemonWares look for the WHMCS client area: Inter font, favicon, brand colour,
 * and a link back to the main site. Upload to WHMCS includes/hooks/.
 */

if (! defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

const LEMONWARES_SITE_URL = 'https://lemonwares.com';

add_hook('ClientAreaHeadOutput', 1, function (array $vars): string {
    $site = LEMONWARES_SITE_URL;

    return <<<HTML
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="{$site}/favicon.ico">
<meta name="theme-color" content="#c51a13">
HTML;
});

add_hook('ClientAreaPrimaryNavbar', 1, function ($primaryNavbar): void {
    if ($primaryNavbar->getChild('lemonwares-site')) {
        return;
    }

    $primaryNavbar->addChild('lemonwares-site', [
        'label' => 'LemonWares.com',
        'uri' => LEMONWARES_SITE_URL,
        'order' => 1,
    ]);
});
