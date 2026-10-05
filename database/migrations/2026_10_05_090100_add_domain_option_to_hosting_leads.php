<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->string('domain_option', 20)->nullable()->after('hostname');
            $table->index('email');
            $table->index('status');
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_reference']);
            $table->dropColumn('domain_option');
        });
    }
};
