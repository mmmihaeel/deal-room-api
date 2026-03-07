<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_space_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_space_id')->constrained('deal_spaces')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('permission', ['view', 'upload', 'share', 'manage']);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->unique(['deal_space_id', 'user_id', 'permission']);
            $table->index(['user_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_space_permissions');
    }
};
