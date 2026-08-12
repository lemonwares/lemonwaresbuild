<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->string('spec_key', 120)->nullable()->after('plan_name');
            $table->string('spec_label', 160)->nullable()->after('spec_key');
            $table->text('spec_summary')->nullable()->after('spec_label');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropColumn(['spec_key', 'spec_label', 'spec_summary']);
        });
    }
};

