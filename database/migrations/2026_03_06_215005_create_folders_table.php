<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('deal_space_id')->constrained('deal_spaces')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['deal_space_id', 'parent_id', 'name']);
            $table->index(['organization_id', 'deal_space_id']);
            $table->index(['deal_space_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
