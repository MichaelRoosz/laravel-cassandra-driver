<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use LaravelCassandraDriver\CassandraServiceProvider;
use LaravelCassandraDriver\CassandraMigrationServiceProvider;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function defineEnvironment($app): void {

        $app['config']->set('database.default', 'cassandra'); /** @phpstan-ignore method.nonObject */
        $app['config']->set('database.connections.cassandra', [ /** @phpstan-ignore method.nonObject */
            'driver' => 'cassandra',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 9042),
            'keyspace' => env('DB_DATABASE', 'test_keyspace'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'page_size' => env('DB_PAGE_SIZE', 5000),
            'consistency' => \LaravelCassandraDriver\Consistency::LOCAL_ONE,
            'timeout' => null,
            'connect_timeout' => 5.0,
            'request_timeout' => 12.0,
        ]);
    }

    protected function getPackageProviders($app): array {
        return [
            CassandraMigrationServiceProvider::class,
            CassandraServiceProvider::class,
        ];
    }

    protected function getRandomKeyspace(): string {
        return 'test_ks_' . bin2hex(random_bytes(8));
    }

    protected function setUpDatabase(): void {
        $this->waitForCassandra();
    }

    protected function waitForCassandra(int $maxAttempts = 30): void {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                DB::connection('cassandra')->select('SELECT now() FROM system.local');

                return;
            } catch (\Exception $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw new \Exception('Cassandra is not available after ' . $maxAttempts . ' attempts: ' . $e->getMessage());
                }
                sleep(2);
            }
        }
    }
}
