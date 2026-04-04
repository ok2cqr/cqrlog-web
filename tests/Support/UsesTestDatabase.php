<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Dibi\Connection;
use LogicException;

trait UsesTestDatabase
{
    private function assertUsingSafeTestDatabase(Connection $connection): void
    {
        $databaseName = (string) $connection->getConfig('database', '');

        if ($databaseName !== '' && str_ends_with($databaseName, '_test')) {
            return;
        }

        throw new LogicException(sprintf(
            'Refusing to run destructive tests against database "%s". Configure a dedicated *_test database first.',
            $databaseName === '' ? '<unknown>' : $databaseName,
        ));
    }
}
