<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->string('dns_provider', 40)->nullable()->after('dns_records');
            $table->timestamp('dns_applied_at')->nullable()->after('dns_provider');
        });
    }

    public function down(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->dropColumn(['dns_provider', 'dns_applied_at']);
        });
    }
};
