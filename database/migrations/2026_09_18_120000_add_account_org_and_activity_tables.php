<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('account_owner_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();
            $table->json('account_permissions')->nullable()->after('account_owner_id');
        });

        Schema::create('account_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->json('permissions')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'email']);
        });

        Schema::create('account_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('actor_type', 20)->default('system');
            $table->string('type', 60);
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->nullableMorphs('related');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_activities');
        Schema::dropIfExists('account_invites');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_owner_id');
            $table->dropColumn('account_permissions');
        });
    }
};
