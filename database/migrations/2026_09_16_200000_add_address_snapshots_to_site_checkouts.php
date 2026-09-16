<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_checkouts', function (Blueprint $table) {
            $table->boolean('shipping_same_as_billing')->default(true)->after('ip_address');
            $table->json('shipping_address')->nullable()->after('shipping_same_as_billing');
            $table->json('billing_snapshot')->nullable()->after('shipping_address');
        });
    }

    public function down(): void
    {
        Schema::table('site_checkouts', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_same_as_billing',
                'shipping_address',
                'billing_snapshot',
            ]);
        });
    }
};
