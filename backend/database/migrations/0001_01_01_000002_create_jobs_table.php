<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('佇列工作的流水號主鍵');
            $table->string('queue')->index()->comment('工作所屬佇列名稱');
            $table->longText('payload')->comment('序列化後的工作內容');
            $table->unsignedTinyInteger('attempts')->comment('工作已嘗試執行次數');
            $table->unsignedInteger('reserved_at')->nullable()->comment('工作被 worker 保留的 Unix timestamp');
            $table->unsignedInteger('available_at')->comment('工作可開始執行的 Unix timestamp');
            $table->unsignedInteger('created_at')->comment('工作建立時間的 Unix timestamp');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('批次工作的唯一識別碼');
            $table->string('name')->comment('批次工作名稱');
            $table->integer('total_jobs')->comment('批次包含的工作總數');
            $table->integer('pending_jobs')->comment('批次尚待完成的工作數');
            $table->integer('failed_jobs')->comment('批次執行失敗的工作數');
            $table->longText('failed_job_ids')->comment('失敗工作識別碼清單');
            $table->mediumText('options')->nullable()->comment('序列化後的批次選項');
            $table->integer('cancelled_at')->nullable()->comment('批次取消時間的 Unix timestamp');
            $table->integer('created_at')->comment('批次建立時間的 Unix timestamp');
            $table->integer('finished_at')->nullable()->comment('批次完成時間的 Unix timestamp');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('失敗工作的流水號主鍵');
            $table->string('uuid')->unique()->comment('失敗工作的全域唯一識別碼');
            $table->text('connection')->comment('工作使用的佇列連線');
            $table->text('queue')->comment('工作所屬佇列名稱');
            $table->longText('payload')->comment('序列化後的原始工作內容');
            $table->longText('exception')->comment('工作失敗時的例外內容');
            $table->timestamp('failed_at')->useCurrent()->comment('工作失敗時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
