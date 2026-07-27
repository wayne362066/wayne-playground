<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('category', 32)->default('feature');
            $table->string('status', 32)->default('submitted');
            $table->string('moderation_status', 32)->default('approved');
            $table->string('visibility', 32)->default('public');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_type', 32)->default('guest');
            $table->string('author_name', 80)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['moderation_status', 'visibility', 'created_at']);
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
