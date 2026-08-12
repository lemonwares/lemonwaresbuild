<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostingLead extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'company',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'plan_slug',
        'plan_name',
        'spec_key',
        'spec_label',
        'spec_summary',
        'billing_cycle',
        'amount_usd',
        'amount_ngn',
        'checkout_provider',
        'payment_provider',
        'payment_reference',
        'payment_status',
        'flutterwave_transaction_id',
        'status',
        'notes',
        'whmcs_pid',
        'checkout_url',
        'source_url',
        'ip_address',
    ];
}

