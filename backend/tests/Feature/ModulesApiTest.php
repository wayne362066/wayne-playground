<?php

namespace Tests\Feature;

use Tests\TestCase;

class ModulesApiTest extends TestCase
{
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
}
