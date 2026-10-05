<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 8);
            $table->string('group', 40);
            $table->string('key', 190);
            $table->longText('value');
            $table->boolean('is_json')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['locale', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_overrides');
    }
};
