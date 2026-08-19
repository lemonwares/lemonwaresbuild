<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whmcs_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whmcs_customer_id')->constrained('whmcs_customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_service_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id')->index();
            $table->string('product_name')->nullable();
            $table->string('domain')->nullable();
            $table->string('username')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whmcs_services');
    }
};
