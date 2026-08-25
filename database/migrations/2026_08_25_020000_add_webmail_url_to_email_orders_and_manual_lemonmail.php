<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->string('webmail_url', 255)->nullable()->after('provision_error');
        });

        DB::table('email_plans')
            ->where('provider', 'lemonmail')
            ->update(['fulfilment_mode' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->dropColumn('webmail_url');
        });

        DB::table('email_plans')
            ->where('provider', 'lemonmail')
            ->update(['fulfilment_mode' => 'auto']);
    }
};
