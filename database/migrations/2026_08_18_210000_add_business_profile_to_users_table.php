<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('job_title', 120)->nullable()->after('company');
            $table->string('trading_name', 160)->nullable()->after('job_title');
            $table->string('website', 190)->nullable()->after('trading_name');
            $table->string('industry', 80)->nullable()->after('website');
            $table->string('tax_id', 80)->nullable()->after('industry');
            $table->string('registration_number', 80)->nullable()->after('tax_id');
            $table->string('billing_address_line_1', 180)->nullable()->after('registration_number');
            $table->string('billing_address_line_2', 180)->nullable()->after('billing_address_line_1');
            $table->string('billing_city', 120)->nullable()->after('billing_address_line_2');
            $table->string('billing_state', 120)->nullable()->after('billing_city');
            $table->string('billing_postcode', 40)->nullable()->after('billing_state');
            $table->string('billing_country', 4)->nullable()->after('billing_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'job_title',
                'trading_name',
                'website',
                'industry',
                'tax_id',
                'registration_number',
                'billing_address_line_1',
                'billing_address_line_2',
                'billing_city',
                'billing_state',
                'billing_postcode',
                'billing_country',
            ]);
        });
    }
};
