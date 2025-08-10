<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use LaravelCassandraDriver\CassandraServiceProvider;
use LaravelCassandraDriver\CassandraMigrationServiceProvider;
use LaravelCassandraDriver\Consistency;
use LaravelCassandraDriver\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;
use RuntimeException;

abstract class TestCase extends BaseTestCase {
    protected string $testKeyspace;

    protected function setUp(): void {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function tearDown(): void {
        $this->dropTestKeyspace();
        $this->cleanupConnections();

        parent::tearDown();
    }

    protected function cleanupConnections(): void {

        $connectionNames = ['cassandra-nokeyspace', 'cassandra-test'];

        foreach ($connectionNames as $connectionName) {
            $connection = DB::connection($connectionName);
            $connection->disconnect();
        }

        DB::purge();
    }

    protected function createTestKeyspace(): void {

        $builder = Schema::connection('cassandra-nokeyspace');
        if (!($builder instanceof SchemaBuilder)) {
            throw new RuntimeException('Schema builder is not a cassandra schema builder');
        }

        $builder->createKeyspace($this->testKeyspace, [
            'class' => 'SimpleStrategy',
            'replication_factor' => 1,
        ]);
    }

    protected function defineEnvironment($app): void {

        $this->testKeyspace = $this->getRandomKeyspace();

        /** @phpstan-ignore method.nonObject */
        $app['config']->set('database.default', 'cassandra-test');

        /** @phpstan-ignore method.nonObject */
        $app['config']->set('database.connections.cassandra-nokeyspace', self::getConnectionConfig());

        /** @phpstan-ignore method.nonObject */
        $app['config']->set('database.connections.cassandra-test', self::getConnectionConfig($this->testKeyspace));
    }

    protected function dropTestKeyspace(): void {

        $builder = Schema::connection('cassandra-nokeyspace');
        if (!($builder instanceof SchemaBuilder)) {
            throw new RuntimeException('Schema builder is not a cassandra schema builder');
        }

        $builder->dropKeyspace($this->testKeyspace);
    }

    /**
     * @return array{
     *  driver: string,
     *  host: string,
     *  port: int,
     *  keyspace: string,
     *  username: string,
     *  password: string,
     *  page_size: int,
     *  consistency: \LaravelCassandraDriver\Consistency,
     *  timeout: float,
     *  connect_timeout: float,
     *  request_timeout: float,
     * }
     */
    protected static function getConnectionConfig(string $keyspace = ''): array {

        $host = env('DB_HOST', '127.0.0.1');
        if (!is_string($host)) {
            throw new RuntimeException('DB_HOST must be a string');
        }

        $port = env('DB_PORT', 9042);
        if (!is_numeric($port)) {
            throw new RuntimeException('DB_PORT must be a number');
        }

        $username = env('DB_USERNAME', '');
        if (!is_string($username)) {
            throw new RuntimeException('DB_USERNAME must be a string');
        }

        $password = env('DB_PASSWORD', '');
        if (!is_string($password)) {
            throw new RuntimeException('DB_PASSWORD must be a string');
        }

        $pageSize = env('DB_PAGE_SIZE', 5000);
        if (!is_numeric($pageSize)) {
            throw new RuntimeException('DB_PAGE_SIZE must be a number');
        }

        return [
            'driver' => 'cassandra',
            'host' => $host,
            'port' => (int) $port,
            'keyspace' => $keyspace,
            'username' => $username,
            'password' => $password,
            'page_size' => (int) $pageSize,
            'consistency' => Consistency::LOCAL_ONE,
            'timeout' => 12.0,
            'connect_timeout' => 5.0,
            'request_timeout' => 12.0,
        ];
    }

    protected function getPackageProviders($app): array {
        return [
            CassandraMigrationServiceProvider::class,
            CassandraServiceProvider::class,
        ];
    }

    protected function getRandomKeyspace(): string {
        return 'test_ks_' . bin2hex(random_bytes(16));
    }

    protected function setUpDatabase(): void {
        $this->waitForCassandra();
        $this->createTestKeyspace();
    }

    protected function waitForCassandra(int $maxAttempts = 3): void {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                DB::connection('cassandra-nokeyspace')->select('SELECT now() FROM system.local');

                return;
            } catch (Exception $e) {

                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw new Exception('Cassandra is not available after ' . $maxAttempts . ' attempts: ' . $e->getMessage());
                }
                sleep(2);
            }
        }
    }
}
