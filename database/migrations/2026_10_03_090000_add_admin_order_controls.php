<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $orderTables = ['site_checkouts', 'domain_checkouts', 'domain_orders'];

    public function up(): void
    {
        foreach ($this->orderTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('admin_notes')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancelled_reason', 500)->nullable();
                $table->decimal('refunded_amount_ngn', 14, 2)->nullable();
                $table->string('refund_reference', 120)->nullable();
                $table->timestamp('refunded_at')->nullable();
            });
        }

        Schema::create('admin_order_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable');
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->text('summary');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_order_events');

        foreach ($this->orderTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'admin_notes',
                    'cancelled_at',
                    'cancelled_reason',
                    'refunded_amount_ngn',
                    'refund_reference',
                    'refunded_at',
                ]);
            });
        }
    }
};
