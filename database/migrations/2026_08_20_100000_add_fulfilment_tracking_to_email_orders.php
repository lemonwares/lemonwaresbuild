<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->string('fulfilment_status', 40)->nullable()->after('fulfilment_mode');
            $table->text('fulfilment_notes')->nullable()->after('fulfilment_status');
            $table->timestamp('fulfilment_updated_at')->nullable()->after('fulfilment_notes');
        });
    }

    public function down(): void
    {
        Schema::table('email_orders', function (Blueprint $table): void {
            $table->dropColumn(['fulfilment_status', 'fulfilment_notes', 'fulfilment_updated_at']);
        });
    }
};
