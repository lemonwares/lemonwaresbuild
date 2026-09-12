<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->string('billing_cycle', 40)->nullable()->after('spec_summary');
            $table->decimal('amount_usd', 12, 2)->nullable()->after('billing_cycle');
            $table->decimal('amount_ngn', 14, 2)->nullable()->after('amount_usd');
            $table->string('checkout_provider', 40)->nullable()->after('amount_ngn');
            $table->string('status', 40)->default('pending')->after('checkout_provider');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_cycle',
                'amount_usd',
                'amount_ngn',
                'checkout_provider',
                'status',
            ]);
        });
    }
};
