<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Test\Integration;

use LaravelCassandraDriver\Tests\TestCase;
use LaravelCassandraDriver\CassandraMigrationRepository;
use LaravelCassandraDriver\Consistency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaravelCassandraDriver\Schema\Blueprint;
use LaravelCassandraDriver\Schema\Builder as SchemaBuilder;

class MigrationTest extends TestCase {
    protected CassandraMigrationRepository $repository;

    protected function setUp(): void {
        parent::setUp();

        $this->repository = new CassandraMigrationRepository(
            $this->app['db'], /** @phpstan-ignore offsetAccess.notFound */
            'migrations'
        );
    }

    protected function tearDown(): void {

        Schema::dropIfExists('migrations');

        parent::tearDown();
    }

    public function testCreateMigrationRepository(): void {
        $this->repository->createRepository();

        $this->assertTrue(Schema::hasTable('migrations'));

        // Verify table structure
        $columns = DB::select('SELECT column_name FROM system_schema.columns WHERE keyspace_name = ? AND table_name = ?',
            [$this->testKeyspace, 'migrations']);

        $columnNames = array_column($columns, 'column_name');

        $this->assertContains('id', $columnNames);
        $this->assertContains('migration', $columnNames);
        $this->assertContains('batch', $columnNames);
    }

    public function testCreateMigrationWithConsistency(): void {
        // Test that migrations can be created with specific consistency levels
        $schema = DB::getSchemaBuilder();
        $this->assertInstanceOf(SchemaBuilder::class, $schema);
        $schema->setConsistency(Consistency::ALL);

        $tableName = 'test_consistency_' . uniqid();

        $schema->create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testDeleteMigration(): void {
        $this->repository->createRepository();

        $migrationName = '2023_01_01_000000_test_migration';
        $this->repository->log($migrationName, 1);

        $ranMigrations = $this->repository->getRan();
        $this->assertContains($migrationName, $ranMigrations);

        $migration = (object) ['migration' => $migrationName];
        $this->repository->delete($migration);

        $ranMigrations = $this->repository->getRan();
        $this->assertNotContains($migrationName, $ranMigrations);
    }

    public function testDeleteRepository(): void {
        $this->repository->createRepository();
        $this->assertTrue($this->repository->repositoryExists());

        $this->repository->deleteRepository();
        $this->assertFalse($this->repository->repositoryExists());
    }

    public function testGetLast(): void {
        $this->repository->createRepository();

        $migrations = [
            '2023_01_01_000000_migration_1',
            '2023_01_02_000000_migration_2',
            '2023_01_03_000000_migration_3',
        ];

        // Log migrations in different batches
        $this->repository->log($migrations[0], 1);
        $this->repository->log($migrations[1], 1);
        $this->repository->log($migrations[2], 2);

        $lastBatch = $this->repository->getLast();

        $this->assertCount(1, $lastBatch);
        $this->assertIsArray($lastBatch[0]);
        $this->assertEquals($migrations[2], $lastBatch[0]['migration']);
    }

    public function testGetLastBatchNumber(): void {
        $this->repository->createRepository();

        $this->assertEquals(0, $this->repository->getLastBatchNumber());

        $this->repository->log('2023_01_01_000000_test_migration_1', 1);
        $this->assertEquals(1, $this->repository->getLastBatchNumber());

        $this->repository->log('2023_01_02_000000_test_migration_2', 2);
        $this->assertEquals(2, $this->repository->getLastBatchNumber());
    }

    public function testGetMigrationBatches(): void {
        $this->repository->createRepository();

        $migrations = [
            '2023_01_01_000000_migration_1' => 1,
            '2023_01_02_000000_migration_2' => 1,
            '2023_01_03_000000_migration_3' => 2,
        ];

        foreach ($migrations as $migration => $batch) {
            $this->repository->log($migration, $batch);
        }

        $batches = $this->repository->getMigrationBatches();

        $this->assertEquals($migrations, $batches);
    }

    public function testGetNextBatchNumber(): void {
        $this->repository->createRepository();

        $this->assertEquals(1, $this->repository->getNextBatchNumber());

        $this->repository->log('2023_01_01_000000_test_migration', 1);
        $this->assertEquals(2, $this->repository->getNextBatchNumber());
    }

    public function testGetRanMigrations(): void {
        $this->repository->createRepository();

        $migrations = [
            '2023_01_01_000000_create_users_table',
            '2023_01_02_000000_create_posts_table',
            '2023_01_03_000000_create_comments_table',
        ];

        foreach ($migrations as $i => $migration) {
            $this->repository->log($migration, $i + 1);
        }

        $ranMigrations = $this->repository->getRan();

        foreach ($migrations as $migration) {
            $this->assertContains($migration, $ranMigrations);
        }
    }

    public function testLogMigration(): void {
        $this->repository->createRepository();

        $migrationName = '2023_01_01_000000_create_test_table';
        $batch = 1;

        $this->repository->log($migrationName, $batch);

        $migrations = $this->repository->getRan();
        $this->assertContains($migrationName, $migrations);
    }

    public function testMigrationIndexCreation(): void {
        $tableName = 'test_index_migration_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('email');
            $table->varchar('status');
            $table->timestamp('created_at');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $table->index('email', "idx_{$tableName}_email_index");
            $table->index('status', "idx_{$tableName}_status_index");
        });

        $indexes = DB::select(
            'SELECT index_name FROM system_schema.indexes WHERE keyspace_name = ? AND table_name = ?',
            [$this->testKeyspace, $tableName]
        );

        $indexNames = array_column($indexes, 'index_name');
        $this->assertContains("idx_{$tableName}_email_index", $indexNames);
        $this->assertContains("idx_{$tableName}_status_index", $indexNames);

        Schema::dropIfExists($tableName);
    }

    public function testMigrationWithComplexStructure(): void {
        $this->repository->createRepository();

        $tableName = 'test_complex_migration_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('tenant_id')->partition();
            $table->uuid('user_id')->partition();
            $table->timestamp('event_date')->clustering('DESC');
            $table->uuid('event_id')->clustering('ASC');
            $table->varchar('event_type');
            $table->text('payload');
            $table->setCollection('tags', 'text');
            $table->mapCollection('metadata', 'text', 'text');
            $table->listCollection('history', 'timestamp');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        $migrationName = "create_{$tableName}_table";
        $this->repository->log($migrationName, 1);

        $ranMigrations = $this->repository->getRan();
        $this->assertContains($migrationName, $ranMigrations);

        Schema::dropIfExists($tableName);
    }

    public function testRepositoryExists(): void {
        $this->assertFalse($this->repository->repositoryExists());

        $this->repository->createRepository();

        $this->assertTrue($this->repository->repositoryExists());
    }
}
