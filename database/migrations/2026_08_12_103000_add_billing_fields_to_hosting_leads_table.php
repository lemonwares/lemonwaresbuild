<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->string('billing_address_line_1')->nullable()->after('company');
            $table->string('billing_address_line_2')->nullable()->after('billing_address_line_1');
            $table->string('billing_city')->nullable()->after('billing_address_line_2');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postcode')->nullable()->after('billing_state');
            $table->string('billing_country', 2)->nullable()->after('billing_postcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->dropColumn([
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

