<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wish_events', function (Blueprint $table): void {
            $table->id()->comment('願望事件流水號主鍵');
            $table->foreignId('wish_id')->comment('事件所屬願望流水號')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40)->comment('願望事件類型');
            $table->string('from_value')->nullable()->comment('事件變更前的值');
            $table->string('to_value')->nullable()->comment('事件變更後的值');
            $table->json('metadata')->nullable()->comment('事件額外結構化資訊');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['wish_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wish_events');
    }
};
