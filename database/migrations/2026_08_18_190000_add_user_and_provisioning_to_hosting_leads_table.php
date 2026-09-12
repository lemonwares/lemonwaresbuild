<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('hostname')->nullable()->after('spec_summary');
            $table->string('ipv4', 45)->nullable()->after('hostname');
            $table->string('panel_url')->nullable()->after('ipv4');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['hostname', 'ipv4', 'panel_url']);
        });
    }
};
