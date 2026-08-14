<?php

namespace App\Tests\Support\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use SensitiveParameter;

/**
 * Counts the SQL statements sent to the database, to catch N+1 queries in
 * tests. Registered for the test environment only.
 */
final class QueryCounter implements Middleware
{
    /** @var list<string> */
    public static array $queries = [];

    public static function reset(): void
    {
        self::$queries = [];
    }

    /**
     * @return list<string> SELECT statements only
     */
    public static function selects(): array
    {
        return array_values(array_filter(self::$queries, static fn (string $sql): bool => str_starts_with(ltrim($sql), 'SELECT')));
    }

    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            public function connect(#[SensitiveParameter] array $params): Connection
            {
                return new class(parent::connect($params)) extends AbstractConnectionMiddleware {
                    public function prepare(string $sql): Statement
                    {
                        QueryCounter::$queries[] = $sql;

                        return parent::prepare($sql);
                    }

                    public function query(string $sql): Result
                    {
                        QueryCounter::$queries[] = $sql;

                        return parent::query($sql);
                    }
                };
            }
        };
    }
}
