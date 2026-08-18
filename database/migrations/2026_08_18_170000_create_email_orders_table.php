<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_key', 40);
            $table->string('plan_name', 80);
            $table->string('domain', 190);
            $table->unsignedSmallInteger('mailbox_count');
            $table->string('billing_cycle', 20);
            $table->decimal('amount_usd', 10, 2)->default(0);
            $table->decimal('amount_ngn', 12, 2)->default(0);
            $table->string('status', 40)->default('awaiting_payment');
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->string('payment_reference', 80)->nullable()->unique();
            $table->string('flutterwave_transaction_id', 80)->nullable();
            $table->string('checkout_url', 1000)->nullable();
            $table->unsignedBigInteger('trekmail_domain_id')->nullable();
            $table->json('dns_records')->nullable();
            $table->text('provision_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('email_mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_order_id')->constrained('email_orders')->cascadeOnDelete();
            $table->string('local_part', 64);
            $table->string('address', 190);
            $table->unsignedBigInteger('trekmail_mailbox_id')->nullable();
            $table->unsignedBigInteger('trekmail_invite_id')->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['email_order_id', 'local_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_mailboxes');
        Schema::dropIfExists('email_orders');
    }
};
