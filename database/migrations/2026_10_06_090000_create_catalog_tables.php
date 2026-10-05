<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('checkout_provider', 20)->default('whmcs');
            $table->string('ui_tone', 20)->default('rose');
            $table->string('whmcs_pid', 20)->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 40)->index();
            $table->string('key', 60);
            $table->string('provider', 30)->nullable();
            $table->json('content')->nullable();
            $table->json('specs')->nullable();
            $table->decimal('price_ngn', 14, 2)->default(0);
            $table->json('cycle_discounts')->nullable();
            $table->json('cycle_prices')->nullable();
            $table->string('whmcs_pid', 20)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        $data = require database_path('data/catalog.php');
        $now = now();

        foreach ($data['groups'] as $group) {
            DB::table('catalog_groups')->insert([
                'key' => $group['key'],
                'checkout_provider' => $group['checkout_provider'],
                'ui_tone' => $group['ui_tone'],
                'content' => json_encode($group['content']),
                'sort_order' => $group['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $savedPrices = Schema::hasTable('hosting_plan_prices')
            ? DB::table('hosting_plan_prices')->get()->keyBy(fn ($row) => strtolower($row->plan_slug).'|'.strtolower($row->spec_key))
            : collect();

        foreach ($data['plans'] as $plan) {
            $saved = $savedPrices->get($plan['group'].'|'.$plan['key']);
            $price = $saved && (float) $saved->price_amount > 0 && strtoupper((string) $saved->currency) === 'NGN'
                ? (float) $saved->price_amount
                : $plan['price_ngn'];

            DB::table('catalog_plans')->insert([
                'group' => $plan['group'],
                'key' => $plan['key'],
                'content' => json_encode($plan['content']),
                'specs' => json_encode($plan['specs']),
                'price_ngn' => $price,
                'is_featured' => $plan['is_featured'],
                'sort_order' => $plan['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::dropIfExists('hosting_plan_prices');
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_plans');
        Schema::dropIfExists('catalog_groups');
    }
};
