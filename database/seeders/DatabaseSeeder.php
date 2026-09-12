<?php

namespace Database\Seeders;

use App\Models\AccountContact;
use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\NewsletterSubscriber;
use App\Support\EmailCatalogSync;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        EmailCatalogSync::sync(true);

        $adminEmail = strtolower((string) env('ADMIN_EMAIL', 'admin@lemonwares.com'));
        $adminPassword = (string) env('ADMIN_PASSWORD', 'password');

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Lemonwares Admin',
                'role' => 'admin',
                'phone' => '+234 906 732 2844',
                'company' => 'Lemonwares Technology',
                'password' => $adminPassword,
                'email_verified_at' => now(),
            ],
        );

        $customer = User::query()->updateOrCreate(
            ['email' => 'amara@brightmedia.ng'],
            [
                'name' => 'Amara Okonkwo',
                'role' => 'customer',
                'phone' => '+234 803 441 2290',
                'company' => 'Bright Media',
                'job_title' => 'Managing Director',
                'trading_name' => 'Bright Media NG',
                'website' => 'https://brightmedia.ng',
                'industry' => 'advertising',
                'tax_id' => 'TIN-BN-2049183',
                'registration_number' => 'RC-1849201',
                'billing_address_line_1' => '14 Admiralty Way',
                'billing_address_line_2' => 'Lekki Phase 1',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '105102',
                'billing_country' => 'NG',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        AccountContact::query()->updateOrCreate(
            [
                'user_id' => $customer->id,
                'email' => 'ops@brightmedia.ng',
            ],
            [
                'name' => 'Chinedu Okeke',
                'role' => 'technical',
                'notify' => true,
                'unavailable_backup' => true,
            ],
        );

        AccountContact::query()->updateOrCreate(
            [
                'user_id' => $customer->id,
                'email' => 'accounts@brightmedia.ng',
            ],
            [
                'name' => 'Ngozi Adeyemi',
                'role' => 'billing',
                'notify' => true,
                'unavailable_backup' => false,
            ],
        );

        $order = EmailOrder::query()->updateOrCreate(
            [
                'user_id' => $customer->id,
                'domain' => 'brightmedia.ng',
            ],
            [
                'plan_key' => 'team',
                'plan_name' => 'Team',
                'mailbox_count' => 5,
                'billing_cycle' => 'annually',
                'amount_usd' => 191.90,
                'amount_ngn' => 191.90 * 1500,
                'status' => 'paid_pending_setup',
                'payment_provider' => 'flutterwave',
                'payment_status' => 'successful',
                'payment_reference' => 'LW-MAIL-DEMO-BRIGHT',
                'dns_records' => [
                    ['type' => 'MX', 'name' => '@', 'value' => 'mx.trekmail.net'],
                    ['type' => 'TXT', 'name' => '@', 'value' => 'v=spf1 include:_spf.trekmail.net ~all'],
                    ['type' => 'TXT', 'name' => '_dmarc', 'value' => 'v=DMARC1; p=none;'],
                ],
                'provision_error' => 'Demo order — add TREKMAIL_API_TOKEN then retry provisioning.',
            ],
        );

        $locals = ['hello', 'info', 'sales', 'support', 'admin'];
        foreach ($locals as $local) {
            EmailMailbox::query()->updateOrCreate(
                [
                    'email_order_id' => $order->id,
                    'local_part' => $local,
                ],
                [
                    'address' => $local . '@brightmedia.ng',
                    'status' => 'pending',
                ],
            );
        }

        HostingLead::query()->updateOrCreate(
            ['email' => 'amara@brightmedia.ng', 'plan_slug' => 'vps'],
            [
                'user_id' => $customer->id,
                'full_name' => 'Amara Okonkwo',
                'phone' => '+2348034412290',
                'company' => 'Bright Media',
                'billing_address_line_1' => '14 Admiralty Way',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '105102',
                'billing_country' => 'NG',
                'plan_name' => 'VPS Root Server',
                'spec_key' => 'starter',
                'spec_label' => 'Starter',
                'spec_summary' => 'Starter VPS (2 vCPU | 4 GB RAM)',
                'hostname' => 'vps.brightmedia.ng',
                'ipv4' => '203.0.113.42',
                'billing_cycle' => 'monthly',
                'amount_usd' => 24,
                'amount_ngn' => 36000,
                'checkout_provider' => 'internal',
                'payment_provider' => 'flutterwave',
                'payment_status' => 'successful',
                'status' => 'provisioned',
                'notes' => 'Demo provisioned VPS for Bright Media.',
            ],
        );

        HostingLead::query()->updateOrCreate(
            ['email' => 'amara@brightmedia.ng', 'plan_slug' => 'cpanel'],
            [
                'user_id' => $customer->id,
                'full_name' => 'Amara Okonkwo',
                'phone' => '+2348034412290',
                'company' => 'Bright Media',
                'billing_address_line_1' => '14 Admiralty Way',
                'billing_city' => 'Lagos',
                'billing_state' => 'Lagos',
                'billing_postcode' => '105102',
                'billing_country' => 'NG',
                'plan_name' => 'Cloud Hosting Powered by cPanel',
                'spec_key' => 'starter',
                'spec_label' => 'Starter',
                'spec_summary' => 'Shared hosting for brightmedia.ng',
                'hostname' => 'brightmedia.ng',
                'panel_url' => config('site.whmcs.client_login_url'),
                'billing_cycle' => 'annually',
                'amount_usd' => 72,
                'amount_ngn' => 108000,
                'checkout_provider' => 'whmcs',
                'payment_status' => 'successful',
                'status' => 'provisioned',
                'notes' => 'Demo cPanel hosting for Bright Media.',
            ],
        );

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => 'amara@brightmedia.ng'],
            ['full_name' => 'Amara Okonkwo'],
        );
    }
}
