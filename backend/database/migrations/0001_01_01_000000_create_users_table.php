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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('使用者流水號主鍵');
            $table->string('name')->comment('使用者顯示名稱');
            $table->string('email')->unique()->comment('使用者登入與聯絡信箱');
            $table->timestamp('email_verified_at')->nullable()->comment('信箱完成驗證的時間');
            $table->string('password')->comment('雜湊後的登入密碼');
            $table->rememberToken()->comment('保持登入狀態使用的權杖');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('要求重設密碼的信箱');
            $table->string('token')->comment('密碼重設驗證權杖');
            $table->timestamp('created_at')->nullable()->comment('權杖建立時間');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session 唯一識別碼');
            $table->foreignId('user_id')->nullable()->index()->comment('登入使用者流水號');
            $table->string('ip_address', 45)->nullable()->comment('Session 來源 IP 位址');
            $table->text('user_agent')->nullable()->comment('Session 瀏覽器識別資訊');
            $table->longText('payload')->comment('序列化後的 Session 內容');
            $table->integer('last_activity')->index()->comment('最後活動時間的 Unix timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
