<?php

namespace App\Support;

use App\Models\IntegrationSetting;

class EmailProviderSettings
{
    /**
     * Manual / partner email suites shown on the admin settings page.
     *
     * @return list<string>
     */
    public static function manualProviders(): array
    {
        return ['titan', 'google_workspace', 'ms365'];
    }

    public static function label(string $provider): string
    {
        return (string) __('email.providers.' . $provider);
    }

    /**
     * @return array{portal_url:string,account_ref:string,api_key:string,api_secret:string,notes:string}
     */
    public static function for(string $provider): array
    {
        $provider = strtolower($provider);

        return [
            'portal_url' => (string) IntegrationSetting::getValue("email.{$provider}.portal_url", ''),
            'account_ref' => (string) IntegrationSetting::getValue("email.{$provider}.account_ref", ''),
            'api_key' => (string) IntegrationSetting::getValue("email.{$provider}.api_key", ''),
            'api_secret' => (string) IntegrationSetting::getValue("email.{$provider}.api_secret", ''),
            'notes' => (string) IntegrationSetting::getValue("email.{$provider}.notes", ''),
        ];
    }

    /**
     * @param  array{portal_url?:string,account_ref?:string,api_key?:string,api_secret?:string,notes?:string}  $data
     */
    public static function put(string $provider, array $data): void
    {
        $provider = strtolower($provider);

        IntegrationSetting::putMany([
            "email.{$provider}.portal_url" => trim((string) ($data['portal_url'] ?? '')),
            "email.{$provider}.account_ref" => trim((string) ($data['account_ref'] ?? '')),
            "email.{$provider}.api_key" => trim((string) ($data['api_key'] ?? '')),
            "email.{$provider}.api_secret" => trim((string) ($data['api_secret'] ?? '')),
            "email.{$provider}.notes" => trim((string) ($data['notes'] ?? '')),
        ]);
    }
}
