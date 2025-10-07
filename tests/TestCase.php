<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * Bootstrapping the application here allows tests to use facades (Bus, Auth, etc.)
     * while keeping tests as unit tests (we won't run migrations or touch DB).
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        // Ensure Mockery expectations are verified
        \Mockery::close();
        parent::tearDown();
    }
}
