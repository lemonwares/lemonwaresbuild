<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->json('images')->nullable()->after('body');
            $table->string('recipient_mode', 20)->default('all')->after('status');
            $table->json('recipient_ids')->nullable()->after('recipient_mode');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['images', 'recipient_mode', 'recipient_ids']);
        });
    }
};
