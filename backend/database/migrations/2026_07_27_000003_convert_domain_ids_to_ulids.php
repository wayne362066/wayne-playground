<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = [];
        $wishIds = [];

        Schema::create('users_ulid', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('使用者 ULID 主鍵');
            $table->string('username', 32)->unique()->comment('使用者登入帳號');
            $table->string('password')->comment('雜湊後的登入密碼');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $id = (string) Str::ulid();
            $userIds[(string) $user->id] = $id;

            DB::table('users_ulid')->insert([
                'id' => $id,
                'username' => $user->username,
                'password' => $user->password,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        Schema::create('sessions_ulid', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('Session 唯一識別碼');
            $table->foreignUlid('user_id')->nullable()->index()->comment('登入使用者 ULID');
            $table->string('ip_address', 45)->nullable()->comment('Session 來源 IP 位址');
            $table->text('user_agent')->nullable()->comment('Session 瀏覽器識別資訊');
            $table->longText('payload')->comment('序列化後的 Session 內容');
            $table->integer('last_activity')->index()->comment('最後活動時間的 Unix timestamp');
        });

        foreach (DB::table('sessions')->get() as $session) {
            DB::table('sessions_ulid')->insert([
                'id' => $session->id,
                'user_id' => $session->user_id === null
                    ? null
                    : ($userIds[(string) $session->user_id] ?? null),
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'payload' => $session->payload,
                'last_activity' => $session->last_activity,
            ]);
        }

        Schema::create('modules_ulid', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('模組 ULID 主鍵');
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

        foreach (DB::table('modules')->orderBy('id')->get() as $module) {
            DB::table('modules_ulid')->insert([
                'id' => (string) Str::ulid(),
                'key' => $module->key,
                'name' => $module->name,
                'description' => $module->description,
                'icon' => $module->icon,
                'route' => $module->route,
                'enabled' => $module->enabled,
                'status' => $module->status,
                'sort_order' => $module->sort_order,
                'created_at' => $module->created_at,
                'updated_at' => $module->updated_at,
            ]);
        }

        Schema::create('wishes_ulid', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('願望 ULID 主鍵');
            $table->string('title', 120)->comment('願望標題');
            $table->text('description')->nullable()->comment('願望詳細說明');
            $table->string('category', 32)->default('feature')->comment('願望分類');
            $table->string('status', 32)->default('submitted')->comment('願望處理進度狀態');
            $table->string('moderation_status', 32)->default('approved')->comment('願望內容審核狀態');
            $table->string('visibility', 32)->default('public')->comment('願望可見範圍');
            $table->foreignUlid('author_id')
                ->nullable()
                ->comment('投稿者使用者 ULID')
                ->constrained('users_ulid')
                ->nullOnDelete();
            $table->string('author_type', 32)->default('guest')->comment('投稿者身分類型');
            $table->string('author_name', 80)->nullable()->comment('投稿時顯示的名稱');
            $table->softDeletes()->comment('願望軟刪除時間');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['moderation_status', 'visibility', 'created_at']);
            $table->index(['status', 'category']);
        });

        foreach (DB::table('wishes')->orderBy('id')->get() as $wish) {
            $id = Str::isUlid($wish->public_id)
                ? (string) $wish->public_id
                : (string) Str::ulid();
            $wishIds[(string) $wish->id] = $id;

            DB::table('wishes_ulid')->insert([
                'id' => $id,
                'title' => $wish->title,
                'description' => $wish->description,
                'category' => $wish->category,
                'status' => $wish->status,
                'moderation_status' => $wish->moderation_status,
                'visibility' => $wish->visibility,
                'author_id' => $wish->author_id === null
                    ? null
                    : ($userIds[(string) $wish->author_id] ?? null),
                'author_type' => $wish->author_type,
                'author_name' => $wish->author_name,
                'deleted_at' => $wish->deleted_at,
                'created_at' => $wish->created_at,
                'updated_at' => $wish->updated_at,
            ]);
        }

        Schema::create('wish_events_ulid', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('願望事件 ULID 主鍵');
            $table->foreignUlid('wish_id')
                ->comment('事件所屬願望 ULID')
                ->constrained('wishes_ulid')
                ->cascadeOnDelete();
            $table->string('event_type', 40)->comment('願望事件類型');
            $table->string('from_value')->nullable()->comment('事件變更前的值');
            $table->string('to_value')->nullable()->comment('事件變更後的值');
            $table->json('metadata')->nullable()->comment('事件額外結構化資訊');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['wish_id', 'created_at']);
        });

        foreach (DB::table('wish_events')->orderBy('id')->get() as $event) {
            DB::table('wish_events_ulid')->insert([
                'id' => (string) Str::ulid(),
                'wish_id' => $wishIds[(string) $event->wish_id],
                'event_type' => $event->event_type,
                'from_value' => $event->from_value,
                'to_value' => $event->to_value,
                'metadata' => $event->metadata,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }

        Schema::drop('wish_events');
        Schema::drop('wishes');
        Schema::drop('sessions');
        Schema::drop('users');
        Schema::drop('modules');

        Schema::rename('users_ulid', 'users');
        Schema::rename('sessions_ulid', 'sessions');
        Schema::rename('modules_ulid', 'modules');
        Schema::rename('wishes_ulid', 'wishes');
        Schema::rename('wish_events_ulid', 'wish_events');
    }

    public function down(): void
    {
        $userIds = [];
        $wishIds = [];

        Schema::create('users_integer', function (Blueprint $table): void {
            $table->id()->comment('使用者流水號主鍵');
            $table->string('username', 32)->unique()->comment('使用者登入帳號');
            $table->string('password')->comment('雜湊後的登入密碼');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });

        foreach (DB::table('users')->orderBy('id')->get()->values() as $index => $user) {
            $id = $index + 1;
            $userIds[(string) $user->id] = $id;

            DB::table('users_integer')->insert([
                'id' => $id,
                'username' => $user->username,
                'password' => $user->password,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        Schema::create('sessions_integer', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('Session 唯一識別碼');
            $table->foreignId('user_id')->nullable()->index()->comment('登入使用者流水號');
            $table->string('ip_address', 45)->nullable()->comment('Session 來源 IP 位址');
            $table->text('user_agent')->nullable()->comment('Session 瀏覽器識別資訊');
            $table->longText('payload')->comment('序列化後的 Session 內容');
            $table->integer('last_activity')->index()->comment('最後活動時間的 Unix timestamp');
        });

        foreach (DB::table('sessions')->get() as $session) {
            DB::table('sessions_integer')->insert([
                'id' => $session->id,
                'user_id' => $session->user_id === null
                    ? null
                    : ($userIds[(string) $session->user_id] ?? null),
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'payload' => $session->payload,
                'last_activity' => $session->last_activity,
            ]);
        }

        Schema::create('modules_integer', function (Blueprint $table): void {
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

        foreach (DB::table('modules')->orderBy('id')->get()->values() as $index => $module) {
            DB::table('modules_integer')->insert([
                'id' => $index + 1,
                'key' => $module->key,
                'name' => $module->name,
                'description' => $module->description,
                'icon' => $module->icon,
                'route' => $module->route,
                'enabled' => $module->enabled,
                'status' => $module->status,
                'sort_order' => $module->sort_order,
                'created_at' => $module->created_at,
                'updated_at' => $module->updated_at,
            ]);
        }

        Schema::create('wishes_integer', function (Blueprint $table): void {
            $table->id()->comment('願望流水號主鍵');
            $table->ulid('public_id')->unique()->comment('對外公開使用的願望 ULID');
            $table->string('title', 120)->comment('願望標題');
            $table->text('description')->nullable()->comment('願望詳細說明');
            $table->string('category', 32)->default('feature')->comment('願望分類');
            $table->string('status', 32)->default('submitted')->comment('願望處理進度狀態');
            $table->string('moderation_status', 32)->default('approved')->comment('願望內容審核狀態');
            $table->string('visibility', 32)->default('public')->comment('願望可見範圍');
            $table->foreignId('author_id')
                ->nullable()
                ->comment('投稿者使用者流水號')
                ->constrained('users_integer')
                ->nullOnDelete();
            $table->string('author_type', 32)->default('guest')->comment('投稿者身分類型');
            $table->string('author_name', 80)->nullable()->comment('投稿時顯示的名稱');
            $table->softDeletes()->comment('願望軟刪除時間');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['moderation_status', 'visibility', 'created_at']);
            $table->index(['status', 'category']);
        });

        foreach (DB::table('wishes')->orderBy('id')->get()->values() as $index => $wish) {
            $id = $index + 1;
            $wishIds[(string) $wish->id] = $id;

            DB::table('wishes_integer')->insert([
                'id' => $id,
                'public_id' => $wish->id,
                'title' => $wish->title,
                'description' => $wish->description,
                'category' => $wish->category,
                'status' => $wish->status,
                'moderation_status' => $wish->moderation_status,
                'visibility' => $wish->visibility,
                'author_id' => $wish->author_id === null
                    ? null
                    : ($userIds[(string) $wish->author_id] ?? null),
                'author_type' => $wish->author_type,
                'author_name' => $wish->author_name,
                'deleted_at' => $wish->deleted_at,
                'created_at' => $wish->created_at,
                'updated_at' => $wish->updated_at,
            ]);
        }

        Schema::create('wish_events_integer', function (Blueprint $table): void {
            $table->id()->comment('願望事件流水號主鍵');
            $table->foreignId('wish_id')
                ->comment('事件所屬願望流水號')
                ->constrained('wishes_integer')
                ->cascadeOnDelete();
            $table->string('event_type', 40)->comment('願望事件類型');
            $table->string('from_value')->nullable()->comment('事件變更前的值');
            $table->string('to_value')->nullable()->comment('事件變更後的值');
            $table->json('metadata')->nullable()->comment('事件額外結構化資訊');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['wish_id', 'created_at']);
        });

        foreach (DB::table('wish_events')->orderBy('id')->get()->values() as $index => $event) {
            DB::table('wish_events_integer')->insert([
                'id' => $index + 1,
                'wish_id' => $wishIds[(string) $event->wish_id],
                'event_type' => $event->event_type,
                'from_value' => $event->from_value,
                'to_value' => $event->to_value,
                'metadata' => $event->metadata,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }

        Schema::drop('wish_events');
        Schema::drop('wishes');
        Schema::drop('sessions');
        Schema::drop('users');
        Schema::drop('modules');

        Schema::rename('users_integer', 'users');
        Schema::rename('sessions_integer', 'sessions');
        Schema::rename('modules_integer', 'modules');
        Schema::rename('wishes_integer', 'wishes');
        Schema::rename('wish_events_integer', 'wish_events');
    }
};
