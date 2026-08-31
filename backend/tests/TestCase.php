<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $environment = $app->environment();
        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if ($environment !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Unsafe test database configuration: environment=%s, connection=%s, database=%s. Tests must use testing + sqlite + :memory:.',
                $environment,
                $connection,
                $database,
            ));
        }

        return $app;
    }

    protected function withCsrf(): static
    {
        return $this
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token');
    }
}
