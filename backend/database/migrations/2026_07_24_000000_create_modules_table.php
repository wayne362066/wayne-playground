<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table): void {
            $table->id()->comment('模組流水號主鍵');
            $table->string('key')->unique()->comment('程式使用的模組唯一鍵');
            $table->string('name')->comment('模組顯示名稱');
            $table->text('description')->nullable()->comment('模組用途說明');
            $table->string('icon')->nullable()->comment('前端顯示使用的圖示鍵');
            $table->string('route')->comment('模組前端入口路徑');
            $table->boolean('enabled')->default(true)->comment('是否開放顯示與使用模組');
            $table->string('status')->default('active')->comment('模組目前生命週期狀態');
            $table->unsignedInteger('sort_order')->default(0)->comment('模組顯示排序值');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
