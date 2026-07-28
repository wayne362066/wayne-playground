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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('快取項目的唯一鍵');
            $table->mediumText('value')->comment('序列化後的快取內容');
            $table->integer('expiration')->index()->comment('快取到期時間的 Unix timestamp');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('分散式鎖的唯一鍵');
            $table->string('owner')->comment('持有分散式鎖的識別值');
            $table->integer('expiration')->index()->comment('分散式鎖到期時間的 Unix timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
