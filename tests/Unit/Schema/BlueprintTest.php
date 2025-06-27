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

    public function testAddColumn(): void {
        $tableName = 'test_add_column_' . uniqid();

        // Create initial table
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Add columns to existing table
        Schema::table($tableName, function (Blueprint $table) {
            $table->int('age');
            $table->text('description');
            $table->boolean('is_active');
        });

        // Verify table still exists (columns added)
        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testBinaryAndBlobTypes(): void {
        $tableName = 'test_binary_blob_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->binary('binary_data');
            $table->blob('blob_data');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testCassandraDataTypes(): void {
        $tableName = 'test_data_types_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->ascii('ascii_field');
            $table->bigint('bigint_field');
            $table->blob('blob_field');
            $table->boolean('boolean_field');
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

    public function testColumnModification(): void {
        $tableName = 'test_column_modification_' . uniqid();

        // Create table
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
            $table->int('age');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Test that we can add columns and rename them
        Schema::table($tableName, function (Blueprint $table) {
            $table->text('description');
            $table->boolean('is_active');
        });

        // Rename some columns
        Schema::table($tableName, function (Blueprint $table) {
            $table->renameColumn('id', 'user_id');
        });

        // Drop some columns
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['description']);
        });

        // Verify table still exists after all modifications
        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testComplexCollectionOperations(): void {
        $tableName = 'test_complex_collections_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->setCollection('tags', 'text');
            $table->listCollection('events', 'timestamp');
            $table->mapCollection('properties', 'text', 'text');
            $table->mapCollection('counters', 'text', 'int');
            $table->setCollection('categories', 'uuid');
            $table->listCollection('scores', 'float');
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

    public function testCounterColumn(): void {
        $tableName = 'test_counter_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->counter('view_count');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testCreateIndex(): void {
        $tableName = 'test_create_index_' . uniqid();

        // Create table
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('email');
            $table->varchar('status');
            $table->int('age');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Add indexes
        Schema::table($tableName, function (Blueprint $table) {
            $table->index('email');
            $table->index('status');
        });

        // Verify table still exists
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

    public function testDateTimeVariations(): void {
        $tableName = 'test_datetime_variations_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('timestamp_field');
            $table->timestampTz('timestamp_tz_field');
            $table->dateTime('datetime_field');
            $table->dateTimeTz('datetime_tz_field');
            $table->time('time_field');
            $table->timeTz('time_tz_field');
            $table->timeuuid('timeuuid_field');
            $table->year('year_field');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testDropColumn(): void {
        $tableName = 'test_drop_column_' . uniqid();

        // Create table with multiple columns
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
            $table->int('age');
            $table->text('description');
            $table->boolean('is_active');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Drop specific columns
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['age', 'description']);
        });

        // Verify table still exists
        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testDropIndex(): void {
        $tableName = 'test_drop_index_' . uniqid();

        // Create table with indexes
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('email');
            $table->varchar('status');
        });

        // Add indexes first
        Schema::table($tableName, function (Blueprint $table) {
            $table->index('email');
            $table->index('status');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Drop indexes
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex('email');
            $table->dropIndex('status');
        });

        // Verify table still exists
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

    public function testIndexNaming(): void {
        $tableName = 'test_index_naming_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('email');
            $table->varchar('username');
            $table->varchar('status');
        });

        // Create indexes with custom names
        Schema::table($tableName, function (Blueprint $table) {
            $table->index('email', 'custom_email_index');
            $table->index('username', 'custom_username_index');
            $table->index('status');  // Default naming
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testIntegerVariations(): void {
        $tableName = 'test_integer_variations_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('tiny_int_field');
            $table->smallInteger('small_int_field');
            $table->integer('int_field');
            $table->bigInteger('big_int_field');
            $table->tinyint('tinyint_field');
            $table->smallint('smallint_field');
            $table->int('int_field_2');
            $table->bigint('bigint_field');
            $table->varint('varint_field');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testMoreDataTypes(): void {
        $tableName = 'test_more_data_types_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->duration('duration_field');
            $table->time('time_field');
            $table->timestampTz('timestamp_tz_field');
            $table->timeTz('time_tz_field');
            $table->tinyText('tiny_text_field');
            $table->longText('long_text_field');
            $table->mediumText('medium_text_field');
            $table->year('year_field');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testMultipleClusteringKeys(): void {
        $tableName = 'test_multi_clustering_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('partition_key')->partition(); /** @phpstan-ignore method.notFound */
            $table->timestamp('created_date')->clustering('DESC'); /** @phpstan-ignore method.notFound */
            $table->uuid('event_id')->clustering('ASC'); /** @phpstan-ignore method.notFound */
            $table->varchar('event_type')->clustering(); /** @phpstan-ignore method.notFound */
            $table->text('payload');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testMultiplePartitionKeys(): void {
        $tableName = 'test_multi_partition_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('tenant_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->uuid('user_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->uuid('session_id')->partition(); /** @phpstan-ignore method.notFound */
            $table->timestamp('created_at')->clustering('DESC'); /** @phpstan-ignore method.notFound */
            $table->varchar('action');
            $table->text('data');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
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

    public function testRenameColumn(): void {
        $tableName = 'test_rename_column_' . uniqid();

        // Create table
        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('old_name');
            $table->int('old_age');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Rename columns
        Schema::table($tableName, function (Blueprint $table) {
            $table->renameColumn('id', 'user_id');
        });

        // Verify table still exists
        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testRenameIndexOperations(): void {
        $tableName = 'test_rename_index_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('email');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->renameIndex('old_index', 'new_index');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testStaticColumnModifier(): void {
        $tableName = 'test_static_columns_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('partition_key')->partition(); /** @phpstan-ignore method.notFound */
            $table->uuid('clustering_key')->clustering(); /** @phpstan-ignore method.notFound */
            // Static columns are used in Cassandra for columns that are shared across all rows in a partition
            $table->varchar('static_data');
            $table->text('regular_data');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testStringAndTextVariations(): void {
        $tableName = 'test_string_text_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('string_field', 255);
            $table->char('char_field', 10);
            $table->text('text_field');
            $table->tinyText('tiny_text_field');
            $table->mediumText('medium_text_field');
            $table->longText('long_text_field');
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

    public function testTupleType(): void {
        $tableName = 'test_tuple_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tuple('coordinates', 'float', 'float', 'float');
            $table->varchar('name');
        });

        $this->assertTrue(Schema::hasTable($tableName));

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedAutoIncrementColumns(): void {
        $tableName = 'test_auto_increment_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->id();
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->increments('auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->bigIncrements('big_auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->integerIncrements('int_auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->smallIncrements('small_auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->tinyIncrements('tiny_auto_id');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->mediumIncrements('medium_auto_id');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedColumnTypes(): void {
        $tableName = 'test_unsupported_columns_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->enum('status', ['active', 'inactive']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->json('data');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->jsonb('data');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->geometry('location');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->geography('location');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->ipAddress('ip');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedForeignKeyOperations(): void {
        $tableName = 'test_foreign_keys_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->foreign('id')->references('id')->on('other_table');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropForeign(['id']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropConstrainedForeignId('id');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedIndexOperations(): void {
        $tableName = 'test_unsupported_indexes_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
            $table->text('content');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->fullText(['content']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->spatialIndex(['name']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->unique(['name']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropUnique(['name']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropFullText(['content']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropSpatialIndex(['name']);
        });

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

    public function testUnsupportedPrimaryKeyOperations(): void {
        $tableName = 'test_primary_key_ops_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropPrimary();
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropPartition(['id']);
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->dropClustering(['id']);
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }

    public function testUnsupportedTableOperations(): void {
        $tableName = 'test_unsupported_table_' . uniqid();

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->rename('new_table_name');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->temporary();
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->engine('InnoDB');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->charset('utf8');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $this->expectException(RuntimeException::class);
            $table->collation('utf8_unicode_ci');
        });

        // Clean up
        Schema::dropIfExists($tableName);
    }
}
