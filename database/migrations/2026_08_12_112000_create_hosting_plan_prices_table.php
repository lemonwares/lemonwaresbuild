<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hosting_plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_slug', 60);
            $table->string('spec_key', 120);
            $table->decimal('price_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('NGN');
            $table->string('billing_cycle', 40)->default('monthly');
            $table->string('display_suffix', 40)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['plan_slug', 'spec_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_plan_prices');
    }
};
