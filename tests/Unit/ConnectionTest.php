<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests\Unit;

use LaravelCassandraDriver\Tests\TestCase;
use LaravelCassandraDriver\Connection;
use LaravelCassandraDriver\Consistency;
use Illuminate\Support\Facades\DB;

class ConnectionTest extends TestCase {
    public function testBasicQuery(): void {
        $result = DB::connection('cassandra')->select('SELECT now() FROM system.local');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testConsistencySettings(): void {
        $connection = DB::connection('cassandra');

        $this->assertInstanceOf(Connection::class, $connection);

        // Test setting consistency
        $connection->setConsistency(Consistency::ALL);
        $this->assertEquals(Consistency::ALL, $connection->getConsistency());

        // Test default consistency
        $connection->setDefaultConsistency();
        $this->assertEquals(Consistency::LOCAL_ONE, $connection->getConsistency());
    }

    public function testDatabaseConnection(): void {
        $connection = DB::connection('cassandra');

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testIgnoreWarnings(): void {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Connection::class, $connection);

        // Test ignore warnings
        $connection->ignoreWarnings();

        // todo: trigger a warning

        // Test enable warnings
        $connection->logWarnings();
    }

    public function testKeyspaceConnection(): void {

        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Connection::class, $connection);

        $keyspace = $connection->getKeyspaceName();

        $this->assertEquals('test_keyspace', $keyspace);
    }

    public function testPageSize(): void {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Connection::class, $connection);

        $pageSize = $connection->getPageSize();
        $this->assertGreaterThan(0, $pageSize);
    }

    public function testQueryBuilder(): void {
        $connection = DB::connection('cassandra');
        $query = $connection->query();

        $this->assertInstanceOf(\LaravelCassandraDriver\Query\Builder::class, $query);
    }

    public function testSchemaBuilder(): void {
        $connection = DB::connection('cassandra');
        $schema = $connection->getSchemaBuilder();

        $this->assertInstanceOf(\LaravelCassandraDriver\Schema\Builder::class, $schema);
    }

    public function testServerVersion(): void {
        $connection = DB::connection('cassandra');

        $version = $connection->getServerVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
}
