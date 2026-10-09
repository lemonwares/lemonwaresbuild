<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_client_id')->index();
            $table->unsignedBigInteger('whmcs_invoice_id')->index();
            $table->decimal('amount_ngn', 14, 2);
            $table->string('payment_reference')->unique();
            $table->string('status')->default('awaiting_payment')->index();
            $table->string('payment_status')->nullable();
            $table->string('flutterwave_transaction_id')->nullable();
            $table->timestamp('whmcs_recorded_at')->nullable()->index();
            $table->unsignedInteger('whmcs_attempts')->default(0);
            $table->text('whmcs_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
