<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Home\Models\Module;
use App\Modules\Wishes\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainUlidMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_models_generate_ulid_primary_and_foreign_keys(): void
    {
        $user = User::factory()->create();
        $module = Module::query()->create([
            'key' => 'ulid-module',
            'name' => 'ULID module',
            'route' => '/ulid',
        ]);
        $wish = Wish::query()->create([
            'title' => 'ULID wish',
            'category' => 'feature',
            'status' => 'submitted',
            'moderation_status' => 'approved',
            'visibility' => 'public',
            'author_id' => $user->id,
            'author_type' => 'guest',
            'author_name' => 'ULID user',
        ]);
        $event = $wish->events()->create([
            'event_type' => 'created',
        ]);

        foreach ([$user->id, $module->id, $wish->id, $event->id] as $id) {
            $this->assertTrue(Str::isUlid($id));
        }

        $this->assertSame($user->id, $wish->author_id);
        $this->assertSame($wish->id, $event->wish_id);
    }

    public function test_existing_domain_records_and_relationships_are_preserved_when_ids_become_ulids(): void
    {
        $migration = require database_path(
            'migrations/2026_07_27_000003_convert_domain_ids_to_ulids.php'
        );
        $migration->down();

        $wishUlid = (string) Str::ulid();
        $now = now()->toDateTimeString();

        DB::table('users')->insert([
            'id' => 10,
            'username' => 'legacy-user',
            'password' => 'hashed-password',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('sessions')->insert([
            'id' => 'legacy-session',
            'user_id' => 10,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => 123,
        ]);
        DB::table('modules')->insert([
            'id' => 20,
            'key' => 'legacy-module',
            'name' => 'Legacy module',
            'description' => null,
            'icon' => null,
            'route' => '/legacy',
            'enabled' => true,
            'status' => 'active',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('wishes')->insert([
            'id' => 30,
            'public_id' => $wishUlid,
            'title' => 'Legacy wish',
            'description' => null,
            'category' => 'feature',
            'status' => 'submitted',
            'moderation_status' => 'approved',
            'visibility' => 'public',
            'author_id' => 10,
            'author_type' => 'guest',
            'author_name' => 'Legacy user',
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('wish_events')->insert([
            'id' => 40,
            'wish_id' => 30,
            'event_type' => 'created',
            'from_value' => null,
            'to_value' => null,
            'metadata' => json_encode(['source' => 'legacy']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration->up();

        $user = User::query()->where('username', 'legacy-user')->sole();
        $wish = Wish::query()->where('title', 'Legacy wish')->sole();
        $event = DB::table('wish_events')->sole();
        $module = DB::table('modules')->where('key', 'legacy-module')->sole();
        $session = DB::table('sessions')->where('id', 'legacy-session')->sole();

        $this->assertTrue(Str::isUlid($user->id));
        $this->assertSame($wishUlid, $wish->id);
        $this->assertSame($user->id, $wish->author_id);
        $this->assertTrue(Str::isUlid($event->id));
        $this->assertSame($wish->id, $event->wish_id);
        $this->assertTrue(Str::isUlid($module->id));
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame($user->id, $wish->author->id);
        $this->assertSame($event->id, $wish->events->sole()->id);
        $this->assertFalse(Schema::hasColumn('wishes', 'public_id'));
    }
}
