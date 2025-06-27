<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests\Unit\Schema;

use LaravelCassandraDriver\Tests\TestCase;
use LaravelCassandraDriver\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BlueprintTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
    }

    public function testCassandraDataTypes(): void {
        $tableName = 'test_data_types_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->ascii('ascii_field');
            $table->bigint('bigint_field');
            $table->blob('blob_field');
            $table->boolean('boolean_field');
            //$table->counter('counter_field');
            $table->decimal('decimal_field');
            $table->float('float_field');
            $table->inet('inet_field');
            $table->int('int_field');
            $table->smallint('smallint_field');
            $table->text('text_field');
            $table->timeuuid('timeuuid_field');
            $table->varchar('varchar_field');
            $table->varint('varint_field');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testClusteringColumns(): void {
        $tableName = 'test_clustering_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('user_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->timestamp('created_at')->clustering('DESC'); /** @phpstan-ignore method.notFound */
            $table->uuid('post_id')->clustering('ASC'); /** @phpstan-ignore method.notFound */
            $table->varchar('title');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testCollectionTypes(): void {
        $tableName = 'test_collections_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->setCollection('tags', 'text');
            $table->listCollection('items', 'int');
            $table->mapCollection('metadata', 'text', 'text');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testComplexTableStructure(): void {
        $tableName = 'test_complex_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('tenant_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->uuid('user_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->timestamp('created_date')->clustering('DESC'); /** @phpstan-ignore method.notFound */
            $table->uuid('event_id')->clustering('ASC'); /** @phpstan-ignore method.notFound */
            $table->varchar('event_type');
            $table->text('payload');
            $table->setCollection('tags', 'text');
            $table->mapCollection('metadata', 'text', 'text');
            $table->timestamp('updated_at');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testCreateTable(): void {
        $tableName = 'test_create_table_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
            $table->int('age');
            $table->timestamp('created_at');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testDropTable(): void {
        $tableName = 'test_drop_table_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        Schema::drop($tableName);

        $this->assertFalse(Schema::hasTable($tableName));
    }

    public function testPartitionKeys(): void {
        $tableName = 'test_partition_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('user_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->uuid('post_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->varchar('title');
            $table->text('content');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testTableExists(): void {
        $tableName = 'test_exists_' . uniqid();

        $this->assertFalse(Schema::hasTable($tableName));

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedOperations(): void {
        $tableName = 'test_unsupported_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->bigIncrements('auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->charset('utf8');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->engine('InnoDB');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }
}
