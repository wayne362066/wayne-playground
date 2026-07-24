<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    public function test_unknown_api_routes_use_the_shared_json_format(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => '找不到指定的 API',
            ]);
    }
}
