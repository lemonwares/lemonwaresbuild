<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->timestamp('period_starts_at')->nullable()->after('provisioned_at');
            $table->timestamp('period_ends_at')->nullable()->after('period_starts_at');
            $table->timestamp('deactivated_at')->nullable()->after('period_ends_at');
            $table->string('deactivated_reason', 40)->nullable()->after('deactivated_at');
            $table->index('period_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->dropIndex(['period_ends_at']);
            $table->dropColumn([
                'period_starts_at',
                'period_ends_at',
                'deactivated_at',
                'deactivated_reason',
            ]);
        });
    }
};
