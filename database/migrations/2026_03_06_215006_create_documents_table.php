<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('deal_space_id')->constrained('deal_spaces')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->string('filename');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('version')->default(1);
            $table->string('checksum', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'deal_space_id']);
            $table->index(['deal_space_id', 'folder_id', 'created_at']);
            $table->index(['organization_id', 'title']);
            $table->index(['owner_user_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
