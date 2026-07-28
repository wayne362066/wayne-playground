<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 32)->nullable()->unique()->after('id')->comment('使用者登入帳號');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropColumn([
                'name',
                'email',
                'email_verified_at',
                'remember_token',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 32)->nullable(false)->comment('使用者登入帳號')->change();
        });

        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('要求重設密碼的信箱');
            $table->string('token')->comment('密碼重設驗證權杖');
            $table->timestamp('created_at')->nullable()->comment('權杖建立時間');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->comment('使用者顯示名稱');
            $table->string('email')->nullable()->unique()->comment('使用者登入與聯絡信箱');
            $table->timestamp('email_verified_at')->nullable()->comment('信箱完成驗證的時間');
            $table->rememberToken()->comment('保持登入狀態使用的權杖');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
