<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table): void {
            $table->string('department', 32)->default('managerial')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table): void {
            $table->dropColumn('department');
        });
    }
};
