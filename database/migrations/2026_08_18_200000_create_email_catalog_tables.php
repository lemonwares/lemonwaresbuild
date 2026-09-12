<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_key', 40)->unique();
            $table->unsignedSmallInteger('mailbox_count')->default(1);
            $table->decimal('monthly_usd', 12, 2)->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('email_billing_cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('cycle_key', 40)->unique();
            $table->unsignedTinyInteger('months')->default(1);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_billing_cycles');
        Schema::dropIfExists('email_plans');
    }
};
