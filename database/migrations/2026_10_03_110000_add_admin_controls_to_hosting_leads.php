<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('hetzner_server_id')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
            $table->dropColumn(['hetzner_server_id', 'admin_notes', 'cancelled_at', 'cancelled_reason']);
        });
    }
};
