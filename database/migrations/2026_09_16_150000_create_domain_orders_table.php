<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 190);
            $table->string('option', 20);
            $table->text('epp_code')->nullable();
            $table->unsignedTinyInteger('reg_period')->default(1);
            $table->decimal('amount_usd', 10, 2)->default(0);
            $table->decimal('amount_ngn', 12, 2)->default(0);
            $table->string('status', 40)->default('awaiting_payment');
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->string('payment_reference', 80)->nullable()->unique();
            $table->string('flutterwave_transaction_id', 80)->nullable();
            $table->string('checkout_url', 1000)->nullable();
            $table->unsignedBigInteger('whmcs_client_id')->nullable();
            $table->unsignedBigInteger('whmcs_order_id')->nullable();
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable();
            $table->string('whmcs_sync_status', 40)->nullable();
            $table->text('whmcs_sync_error')->nullable();
            $table->timestamp('whmcs_synced_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['domain', 'option']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_orders');
    }
};
