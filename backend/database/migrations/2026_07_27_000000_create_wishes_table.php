<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table): void {
            $table->id()->comment('願望流水號主鍵');
            $table->ulid('public_id')->unique()->comment('對外公開使用的願望 ULID');
            $table->string('title', 120)->comment('願望標題');
            $table->text('description')->nullable()->comment('願望詳細說明');
            $table->string('category', 32)->default('feature')->comment('願望分類');
            $table->string('status', 32)->default('submitted')->comment('願望處理進度狀態');
            $table->string('moderation_status', 32)->default('approved')->comment('願望內容審核狀態');
            $table->string('visibility', 32)->default('public')->comment('願望可見範圍');
            $table->foreignId('author_id')->nullable()->comment('投稿者使用者流水號')->constrained('users')->nullOnDelete();
            $table->string('author_type', 32)->default('guest')->comment('投稿者身分類型');
            $table->string('author_name', 80)->nullable()->comment('投稿時顯示的名稱');
            $table->softDeletes()->comment('願望軟刪除時間');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['moderation_status', 'visibility', 'created_at']);
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
