<?php

namespace Tests\Feature;

use Tests\TestCase;

final class TestDatabaseIsolationTest extends TestCase
{
    public function test_tests_use_the_isolated_in_memory_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('array', config('session.driver'));
    }
}
