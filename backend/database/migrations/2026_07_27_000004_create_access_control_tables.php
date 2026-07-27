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
            $table->ulid('id')->primary();
            $table->string('key', 64)->unique();
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key', 100)->unique();
            $table->string('module', 64)->index();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignUlid('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignUlid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('authorization_audits', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('subject_type', 40);
            $table->ulid('subject_id')->nullable();
            $table->string('action', 80);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

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
