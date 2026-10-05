<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description', 255)->nullable();
            $table->string('type', 10)->default('percent');
            $table->decimal('value', 12, 2);
            $table->json('applies_to')->nullable();
            $table->decimal('min_order_ngn', 14, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('site_checkouts', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->decimal('discount_ngn', 14, 2)->nullable();
            $table->decimal('discount_usd', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_checkouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_ngn', 'discount_usd']);
        });
        Schema::dropIfExists('coupons');
    }
};
