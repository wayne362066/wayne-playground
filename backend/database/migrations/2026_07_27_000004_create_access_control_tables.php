<?php

use App\Core\Access\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('角色 ULID 主鍵');
            $table->string('key', 64)->unique()->comment('程式使用的角色唯一鍵');
            $table->string('name', 80)->comment('角色顯示名稱');
            $table->text('description')->nullable()->comment('角色用途說明');
            $table->boolean('is_system')->default(false)->comment('是否為不可刪除的系統角色');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('權限 ULID 主鍵');
            $table->string('key', 100)->unique()->comment('程式授權檢查使用的權限唯一鍵');
            $table->string('module', 64)->index()->comment('權限所屬模組鍵');
            $table->string('name', 100)->comment('權限顯示名稱');
            $table->text('description')->nullable()->comment('權限用途說明');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignUlid('permission_id')->comment('授予角色的權限 ULID')->constrained()->cascadeOnDelete();
            $table->foreignUlid('role_id')->comment('取得權限的角色 ULID')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable()->comment('關聯建立時間');
            $table->timestamp('updated_at')->nullable()->comment('關聯最後更新時間');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignUlid('role_id')->comment('指派給使用者的角色 ULID')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->comment('取得角色的使用者 ULID')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable()->comment('關聯建立時間');
            $table->timestamp('updated_at')->nullable()->comment('關聯最後更新時間');
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('authorization_audits', function (Blueprint $table): void {
            $table->ulid('id')->primary()->comment('授權稽核紀錄 ULID 主鍵');
            $table->foreignUlid('actor_id')
                ->nullable()
                ->comment('執行異動的使用者 ULID')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('subject_type', 40)->comment('被異動資源的類型');
            $table->ulid('subject_id')->nullable()->comment('被異動資源的 ULID');
            $table->string('action', 80)->comment('授權異動動作鍵');
            $table->json('before')->nullable()->comment('異動前的結構化內容');
            $table->json('after')->nullable()->comment('異動後的結構化內容');
            $table->string('ip_address', 45)->nullable()->comment('執行異動的來源 IP 位址');
            $table->timestamp('created_at')->nullable()->comment('資料建立時間');
            $table->timestamp('updated_at')->nullable()->comment('資料最後更新時間');

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });

        $now = now();
        $permissionIds = [];
        $permissionDefinitions = app(PermissionCatalog::class)->all();

        foreach ($permissionDefinitions as $permission) {
            $id = (string) Str::ulid();
            $permissionIds[$permission['key']] = $id;

            DB::table('permissions')->insert([
                'id' => $id,
                'key' => $permission['key'],
                'module' => $permission['module'],
                'name' => $permission['name'],
                'description' => $permission['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = [];

        foreach (config('access.roles', []) as $key => $role) {
            $id = (string) Str::ulid();
            $roleIds[$key] = $id;

            DB::table('roles')->insert([
                'id' => $id,
                'key' => $key,
                'name' => $role['name'],
                'description' => $role['description'] ?? null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($permissionDefinitions as $permission) {
            $roleKeys = array_values(array_unique([
                ...$permission['default_roles'],
                'admin',
            ]));

            foreach ($roleKeys as $roleKey) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionIds[$permission['key']],
                    'role_id' => $roleIds[$roleKey],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('role_user')->insert([
                'role_id' => $roleIds['admin'],
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('authorization_audits')->insert([
                'id' => (string) Str::ulid(),
                'actor_id' => null,
                'subject_type' => 'user',
                'subject_id' => $userId,
                'action' => 'authorization.bootstrap_admin',
                'before' => json_encode([]),
                'after' => json_encode(['roles' => ['admin']]),
                'ip_address' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_audits');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
