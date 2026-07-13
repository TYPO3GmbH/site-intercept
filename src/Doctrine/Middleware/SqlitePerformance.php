<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final readonly class SqlitePerformance implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            public function connect(
                #[SensitiveParameter]
                array $params,
            ): Connection {
                $connection = parent::connect($params);

                $pragmas = implode('; ', [
                    'PRAGMA auto_vacuum = INCREMENTAL',
                    'PRAGMA journal_mode = WAL',
                    'PRAGMA synchronous = NORMAL',
                    'PRAGMA busy_timeout = 5000',
                    'PRAGMA cache_size = -20000',
                    'PRAGMA temp_store = MEMORY',
                ]);
                $connection->exec($pragmas);

                return $connection;
            }
        };
    }
}
