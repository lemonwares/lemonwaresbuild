<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('item_count')->default(0);
            $table->decimal('amount_usd', 12, 2)->default(0);
            $table->decimal('amount_ngn', 14, 2)->default(0);
            $table->string('status', 40)->default('awaiting_payment');
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->string('payment_reference', 80)->nullable()->unique();
            $table->string('flutterwave_transaction_id', 80)->nullable();
            $table->string('checkout_url', 1000)->nullable();
            $table->string('fulfilment_status', 40)->nullable();
            $table->text('fulfilment_error')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('site_checkout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_checkout_id')->constrained('site_checkouts')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('label', 255);
            $table->decimal('amount_usd', 12, 2)->default(0);
            $table->decimal('amount_ngn', 14, 2)->default(0);
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('domain_order_id')->nullable();
            $table->unsignedBigInteger('domain_checkout_id')->nullable();
            $table->unsignedBigInteger('email_order_id')->nullable();
            $table->unsignedBigInteger('hosting_lead_id')->nullable();
            $table->string('fulfilment_status', 40)->nullable();
            $table->text('fulfilment_error')->nullable();
            $table->timestamps();

            $table->index(['site_checkout_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_checkout_items');
        Schema::dropIfExists('site_checkouts');
    }
};
