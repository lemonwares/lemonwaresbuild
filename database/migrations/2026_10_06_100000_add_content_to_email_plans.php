<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_plans', function (Blueprint $table) {
            $table->json('content')->nullable()->after('plan_key');
        });

        Schema::table('email_orders', function (Blueprint $table) {
            $table->json('renewal_reminders')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('email_plans', function (Blueprint $table) {
            $table->dropColumn('content');
        });

        Schema::table('email_orders', function (Blueprint $table) {
            $table->dropColumn('renewal_reminders');
        });
    }
};
