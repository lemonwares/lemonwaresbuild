<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->string('provider', 30)->default('flutterwave');
            $table->string('kind', 20)->default('initial');
            $table->string('reference', 120)->nullable()->index();
            $table->string('transaction_id', 120);
            $table->decimal('amount_ngn', 14, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 30)->default('successful');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
