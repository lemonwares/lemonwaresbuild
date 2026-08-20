<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_plans', function (Blueprint $table): void {
            $table->string('provider', 40)->default('lemonmail')->after('plan_key');
            $table->string('fulfilment_mode', 20)->default('auto')->after('provider');
        });

        Schema::table('email_orders', function (Blueprint $table): void {
            $table->string('provider', 40)->default('lemonmail')->after('plan_name');
            $table->string('fulfilment_mode', 20)->default('auto')->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'fulfilment_mode']);
        });

        Schema::table('email_plans', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'fulfilment_mode']);
        });
    }
};

