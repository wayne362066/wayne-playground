<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_enabled_modules_in_sort_order(): void
    {
        $this->getJson('/api/modules')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.key', 'lottery')
            ->assertJsonPath('data.1.key', 'tarot')
            ->assertJsonPath('data.1.status', 'active')
            ->assertJsonPath('data.2.key', 'wishes')
            ->assertJsonCount(3, 'data');
    }

    public function test_modules_without_view_permission_are_not_returned(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'key' => 'no-modules',
            'name' => 'No modules',
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->getJson('/api/modules')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
