<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FeatureTestCase extends BaseTestCase
{
    use CreatesApplication;
    use LazilyRefreshDatabase {
        migrateFreshUsing as traitMigrateFreshUsing;
    }

    /**
     * @return mixed[]
     */
    protected function migrateFreshUsing(): array
    {
        // We are squashing the migrations which results in the the schema dump file in the name of the default DB connection. Since tests use a different DB connection, they inherently fail.
        // The default Laravel migrate command takes the schema path from the main DB connection. But our tests use a separate DB connection. Hence, we are passing the 'schema path' flag to use the default DB connection schema file during test migrations.

        return array_merge(
            $this->traitMigrateFreshUsing(),
            [
                '--schema-path' => 'database/schema/mysql-schema.sql',
            ]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('activitylog.enabled', false);
    }
}
