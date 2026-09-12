<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->decimal('hosting_amount_usd', 14, 2)->nullable()->after('amount_ngn');
            $table->decimal('hosting_amount_ngn', 14, 2)->nullable()->after('hosting_amount_usd');
            $table->decimal('domain_amount_usd', 14, 2)->nullable()->after('hosting_amount_ngn');
            $table->decimal('domain_amount_ngn', 14, 2)->nullable()->after('domain_amount_usd');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'hosting_amount_usd',
                'hosting_amount_ngn',
                'domain_amount_usd',
                'domain_amount_ngn',
            ]);
        });
    }
};
