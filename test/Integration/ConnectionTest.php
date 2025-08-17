<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Test\Integration;

use LaravelCassandraDriver\Test\TestCase;
use LaravelCassandraDriver\Connection;
use LaravelCassandraDriver\Consistency;
use Illuminate\Support\Facades\DB;

class ConnectionTest extends TestCase {
    public function testBasicQuery(): void {
        $result = DB::connection('cassandra-test')->select('SELECT now() FROM system.local');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testConsistencySettings(): void {
        $connection = DB::connection('cassandra-test');

        $this->assertInstanceOf(Connection::class, $connection);

        // Test setting consistency
        $connection->setConsistency(Consistency::ALL);
        $this->assertEquals(Consistency::ALL, $connection->getConsistency());

        // Test default consistency
        $connection->setDefaultConsistency();
        $this->assertEquals(Consistency::LOCAL_ONE, $connection->getConsistency());
    }

    public function testDatabaseConnection(): void {
        $connection = DB::connection('cassandra-test');

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testKeyspaceConnection(): void {

        $connection = DB::connection('cassandra-test');
        $this->assertInstanceOf(Connection::class, $connection);

        $keyspace = $connection->getKeyspaceName();

        $this->assertEquals($this->testKeyspace, $keyspace);
    }

    public function testPageSize(): void {
        $connection = DB::connection('cassandra-test');
        $this->assertInstanceOf(Connection::class, $connection);

        $pageSize = $connection->getPageSize();
        $this->assertGreaterThan(0, $pageSize);
    }

    public function testQueryBuilder(): void {
        $connection = DB::connection('cassandra-test');
        $query = $connection->query();

        $this->assertInstanceOf(\LaravelCassandraDriver\Query\Builder::class, $query);
    }

    public function testSchemaBuilder(): void {
        $connection = DB::connection('cassandra-test');
        $schema = $connection->getSchemaBuilder();

        $this->assertInstanceOf(\LaravelCassandraDriver\Schema\Builder::class, $schema);
    }

    public function testServerVersion(): void {
        $connection = DB::connection('cassandra-test');

        $version = $connection->getServerVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
}
