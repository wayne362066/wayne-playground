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
            $table->ulid('id')->primary();
            $table->string('username', 32)->unique();
            $table->string('password');
            $table->timestamps();
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
            $table->string('id')->primary();
            $table->foreignUlid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
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
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route');
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
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
            $table->ulid('id')->primary();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('category', 32)->default('feature');
            $table->string('status', 32)->default('submitted');
            $table->string('moderation_status', 32)->default('approved');
            $table->string('visibility', 32)->default('public');
            $table->foreignUlid('author_id')
                ->nullable()
                ->constrained('users_ulid')
                ->nullOnDelete();
            $table->string('author_type', 32)->default('guest');
            $table->string('author_name', 80)->nullable();
            $table->softDeletes();
            $table->timestamps();

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
            $table->ulid('id')->primary();
            $table->foreignUlid('wish_id')
                ->constrained('wishes_ulid')
                ->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

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
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('password');
            $table->timestamps();
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
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
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
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route');
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
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
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('category', 32)->default('feature');
            $table->string('status', 32)->default('submitted');
            $table->string('moderation_status', 32)->default('approved');
            $table->string('visibility', 32)->default('public');
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users_integer')
                ->nullOnDelete();
            $table->string('author_type', 32)->default('guest');
            $table->string('author_name', 80)->nullable();
            $table->softDeletes();
            $table->timestamps();

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
            $table->id();
            $table->foreignId('wish_id')
                ->constrained('wishes_integer')
                ->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

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
