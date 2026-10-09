<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whmcs_customers', function (Blueprint $table): void {
            $table->decimal('credit', 12, 2)->nullable()->after('country');
            $table->string('currency_code', 8)->nullable()->after('credit');
            $table->timestamp('details_synced_at')->nullable()->index()->after('last_synced_at');
        });

        Schema::table('whmcs_services', function (Blueprint $table): void {
            $table->unsignedBigInteger('whmcs_product_id')->nullable()->index()->after('whmcs_client_id');
            $table->decimal('amount', 12, 2)->nullable()->after('billing_cycle');
            $table->date('registration_date')->nullable()->after('amount');
            $table->string('server_name')->nullable()->after('username');
        });

        Schema::create('whmcs_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_domain_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id')->index();
            $table->string('domain')->index();
            $table->string('registrar')->nullable();
            $table->string('status')->nullable()->index();
            $table->date('registration_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->date('next_due_date')->nullable();
            $table->decimal('recurring_amount', 12, 2)->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('id_protection')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('whmcs_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_invoice_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id')->index();
            $table->string('invoice_num')->nullable();
            $table->string('status')->nullable()->index();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->dateTime('date_paid')->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('credit', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->string('currency_code', 8)->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('whmcs_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_order_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id')->index();
            $table->string('order_num')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->dateTime('placed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('whmcs_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('whmcs_ticket_id')->unique();
            $table->string('tid')->nullable()->index();
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('priority')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('last_reply_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        // Older syncs stored service passwords from GetClientsProducts in plain text.
        DB::table('whmcs_services')->update(['raw_payload' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whmcs_tickets');
        Schema::dropIfExists('whmcs_orders');
        Schema::dropIfExists('whmcs_invoices');
        Schema::dropIfExists('whmcs_domains');

        Schema::table('whmcs_services', function (Blueprint $table): void {
            $table->dropIndex(['whmcs_product_id']);
            $table->dropColumn(['whmcs_product_id', 'amount', 'registration_date', 'server_name']);
        });

        Schema::table('whmcs_customers', function (Blueprint $table): void {
            $table->dropIndex(['details_synced_at']);
            $table->dropColumn(['credit', 'currency_code', 'details_synced_at']);
        });
    }
};
