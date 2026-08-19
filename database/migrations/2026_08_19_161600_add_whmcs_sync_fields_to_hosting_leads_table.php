<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->after('whmcs_pid');
            $table->unsignedBigInteger('whmcs_order_id')->nullable()->after('whmcs_client_id');
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable()->after('whmcs_order_id');
            $table->string('whmcs_sync_status', 40)->nullable()->after('whmcs_invoice_id');
            $table->text('whmcs_sync_error')->nullable()->after('whmcs_sync_status');
            $table->timestamp('whmcs_synced_at')->nullable()->after('whmcs_sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'whmcs_client_id',
                'whmcs_order_id',
                'whmcs_invoice_id',
                'whmcs_sync_status',
                'whmcs_sync_error',
                'whmcs_synced_at',
            ]);
        });
    }
};
