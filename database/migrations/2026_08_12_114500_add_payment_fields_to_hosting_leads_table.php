<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->string('payment_provider', 40)->nullable()->after('checkout_provider');
            $table->string('payment_reference', 120)->nullable()->after('payment_provider');
            $table->string('payment_status', 40)->nullable()->after('payment_reference');
            $table->string('flutterwave_transaction_id', 80)->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_provider',
                'payment_reference',
                'payment_status',
                'flutterwave_transaction_id',
            ]);
        });
    }
};
