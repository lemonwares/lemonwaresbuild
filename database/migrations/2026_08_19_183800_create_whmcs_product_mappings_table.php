<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whmcs_product_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_slug');
            $table->string('spec_key');
            $table->unsignedBigInteger('whmcs_pid');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['plan_slug', 'spec_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whmcs_product_mappings');
    }
};
