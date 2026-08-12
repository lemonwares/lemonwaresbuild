<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table): void {
            $table->string('quote', 255)->nullable()->after('role');
            $table->string('x_url')->nullable()->after('bio');
            $table->string('linkedin_url')->nullable()->after('x_url');
            $table->string('instagram_url')->nullable()->after('linkedin_url');
            $table->string('facebook_url')->nullable()->after('instagram_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table): void {
            $table->dropColumn([
                'quote',
                'x_url',
                'linkedin_url',
                'instagram_url',
                'facebook_url',
            ]);
        });
    }
};

