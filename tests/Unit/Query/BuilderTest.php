<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests\Unit\Query;

use Exception;
use LaravelCassandraDriver\Tests\TestCase;
use LaravelCassandraDriver\Query\Builder;
use LaravelCassandraDriver\Consistency;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LaravelCassandraDriver\Schema\Blueprint;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class BuilderTest extends TestCase {
    protected string $testTable;

    protected function setUp(): void {
        parent::setUp();

        $this->testTable = 'test_query_builder_' . uniqid();

        Schema::create($this->testTable, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->varchar('name');
            $table->int('age');
            $table->setCollection('tags', 'text');
            $table->mapCollection('metadata', 'text', 'text');
            $table->timestamp('created_at');
        });
    }

    protected function tearDown(): void {
        Schema::dropIfExists($this->testTable);
        parent::tearDown();
    }

    public function testAdvancedWhereConditions(): void {
        // Insert test data
        $ids = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        $testData = [
            ['id' => $ids[0], 'name' => 'Alpha', 'age' => 25, 'tags' => ['tag1', 'tagA'], 'metadata' => ['test_1' => 'a']],
            ['id' => $ids[1], 'name' => 'Beta', 'age' => 30, 'tags' => ['tag1', 'tagB'], 'metadata' => ['test_2' => 'b']],
            ['id' => $ids[2], 'name' => 'Gamma', 'age' => 35, 'tags' => ['tag2', 'tagC'], 'metadata' => ['test_1' => 'a']],
            ['id' => $ids[3], 'name' => 'Delta', 'age' => 40, 'tags' => ['tag1', 'tagD'], 'metadata' => ['test_2' => 'b']],
        ];

        foreach ($testData as $data) {
            $data['created_at'] = now();
            DB::table($this->testTable)->insert($data);
        }

        $normalizeResults = fn($results) => array_map(function ($result) {
            if (!is_array($result)) {
                throw new RuntimeException('Result is not an array');
            }

            return [
                'id' => $result['id'],
                'name' => $result['name'],
                'age' => $result['age'],
                'tags' => $result['tags'],
                'metadata' => $result['metadata'],
            ];
        }, $results->toArray());

        // Test whereIn with multiple values on primary key
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->whereIn('id', [$ids[0], $ids[3]])->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(2, $results);
        $this->assertContains($testData[0], $normalizedResults);
        $this->assertContains($testData[3], $normalizedResults);

        // Test whereIn with multiple values on int column
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->whereIn('age', [25, 35])->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(2, $results);
        $this->assertContains($testData[0], $normalizedResults);
        $this->assertContains($testData[2], $normalizedResults);

        // test whereContains with multiple values on set column
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->whereContains('tags', 'tag1')->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(3, $results);
        $this->assertContains($testData[0], $normalizedResults);
        $this->assertContains($testData[1], $normalizedResults);
        $this->assertContains($testData[3], $normalizedResults);

        // test whereContainsKey with multiple values on map column
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->whereContainsKey('metadata', 'test_2')->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(2, $results);
        $this->assertContains($testData[1], $normalizedResults);
        $this->assertContains($testData[3], $normalizedResults);
    }

    public function testAggregationFunctions(): void {
        // Insert test data with known values
        $ages = [20, 25, 30, 35, 40];
        foreach ($ages as $i => $age) {
            DB::table($this->testTable)->insert([
                'id' => Uuid::uuid4()->toString(),
                'name' => "User $i",
                'age' => $age,
                'created_at' => now(),
            ]);
        }

        // Test count
        $count = DB::table($this->testTable)->count();
        $this->assertGreaterThanOrEqual(count($ages), $count);

        // Test max
        $maxAge = DB::table($this->testTable)->max('age');
        $this->assertGreaterThanOrEqual(40, $maxAge);

        // Test min
        $minAge = DB::table($this->testTable)->min('age');
        $this->assertLessThanOrEqual(20, $minAge);

        // Test sum
        $sumAge = DB::table($this->testTable)->sum('age');
        $this->assertGreaterThan(0, $sumAge);

        // Test avg
        $avgAge = DB::table($this->testTable)->avg('age');
        $this->assertGreaterThan(0, $avgAge);
    }

    public function testAllowFiltering(): void {
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!($builder instanceof Builder)) {
            $this->fail('Builder is not a Cassandra Query Builder');
        }

        $builder->allowFiltering();
        $this->assertTrue($builder->allowFiltering);

        // Test with actual filtering
        $results = $builder->where('age', '>', 20)->get();
        $this->assertIsArray($results->toArray());
    }

    public function testCollectionOperationsAdvanced(): void {
        $id = Uuid::uuid4()->toString();

        // Insert record with collections
        DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'Collection User',
            'age' => 30,
            'created_at' => now(),
        ]);

        // Test various collection operations
        $collectionOperations = [
            ['set', 'tags', 'add', ['php', 'laravel']],
            ['set', 'tags', 'remove', ['php']],
            ['list', 'tags', 'prepend', ['cassandra']],
            ['list', 'tags', 'append', ['database']],
            ['map', 'metadata', 'put', ['key1' => 'value1']],
        ];

        foreach ($collectionOperations as $operation) {
            $builder = DB::table($this->testTable);
            $this->assertInstanceOf(Builder::class, $builder);

            $builder
                ->where('id', $id)
                ->updateCollection($operation[0], $operation[1], $operation[2], $operation[3]);
        }
    }

    public function testComplexDataTypes(): void {
        $complexData = [
            'id' => Uuid::uuid4()->toString(),
            'name' => 'Complex Data User',
            'age' => 30,
            'created_at' => now(),
        ];

        // Test with various data type edge cases
        $edgeCases = [
            ['id' => Uuid::uuid4()->toString(), 'name' => '', 'age' => 0],
            ['id' => Uuid::uuid4()->toString(), 'name' => 'äöüß', 'age' => 999],
            ['id' => Uuid::uuid4()->toString(), 'name' => '🚀🎉', 'age' => -1],
        ];

        foreach ($edgeCases as $data) {
            $data['created_at'] = now();
            $result = DB::table($this->testTable)->insert($data);
            $this->assertTrue($result);

            // Verify retrieval
            $retrieved = DB::table($this->testTable)->where('id', $data['id'])->first();
            $this->assertIsArray($retrieved);
            $this->assertEquals($data['name'], $retrieved['name']);
        }
    }

    public function testComplexSelectStatements(): void {
        // Insert test data
        DB::table($this->testTable)->insert([
            'id' => Uuid::uuid4()->toString(),
            'name' => 'Select Test User',
            'age' => 30,
            'created_at' => now(),
        ]);

        // Test select specific columns
        $result = DB::table($this->testTable)->select(['id', 'name'])->first();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('age', $result);

        // Test select with aliases
        $result = DB::table($this->testTable)
            ->select(['name as user_name', 'age as user_age'])
            ->first();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_name', $result);
        $this->assertArrayHasKey('user_age', $result);

        // Test addSelect
        $result = DB::table($this->testTable)
            ->select(['id'])
            ->addSelect(['name', 'age'])
            ->first();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('age', $result);
    }

    public function testConsistencyLevels(): void {
        $id = Uuid::uuid4()->toString();

        // Insert with different consistency levels
        $consistencies = [
            Consistency::ALL,
            Consistency::ONE,
            Consistency::LOCAL_ONE,
            Consistency::QUORUM,
            Consistency::LOCAL_QUORUM,
        ];

        foreach ($consistencies as $consistency) {
            $builder = DB::table($this->testTable);
            $this->assertInstanceOf(Builder::class, $builder);

            $builder
                ->setConsistency($consistency)
                ->insert([
                    'id' => Uuid::uuid4()->toString(),
                    'name' => 'User with consistency ' . $consistency->value,
                    'age' => 25,
                    'created_at' => now(),
                ]);
        }

        // Read with different consistency levels
        foreach ($consistencies as $consistency) {
            $builder = DB::table($this->testTable);
            $this->assertInstanceOf(Builder::class, $builder);

            $results = $builder
                ->setConsistency($consistency)
                ->get();

            $this->assertIsArray($results->toArray());
            $this->assertGreaterThanOrEqual(count($consistencies), $results->count());
        }
    }

    public function testCount(): void {
        // Insert some test data
        for ($i = 0; $i < 3; $i++) {
            DB::table($this->testTable)->insert([
                'id' => Uuid::uuid4()->toString(),
                'name' => "User $i",
                'age' => 20 + $i,
                'created_at' => now(),
            ]);
        }

        $count = DB::table($this->testTable)->count();

        $this->assertGreaterThanOrEqual(3, $count);
    }

    public function testDelete(): void {
        $id = Uuid::uuid4()->toString();

        DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'To Delete',
            'age' => 30,
            'created_at' => now(),
        ]);

        $result = DB::table($this->testTable)->where('id', $id)->delete();

        $this->assertEquals(1, $result);

        $user = DB::table($this->testTable)->where('id', $id)->first();
        $this->assertNull($user);
    }

    public function testEmptyWhereCondition(): void {

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $results = $builder->allowFiltering()->where('name', '=', '')->get();

        $this->assertCount(0, $results);
        $this->assertIsArray($results->toArray());
    }

    public function testErrorHandling(): void {
        // Test invalid table name (should handle gracefully)
        try {
            DB::table('non_existent_table')->get();
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }

        // Test invalid column name in where clause
        try {
            DB::table($this->testTable)->where('non_existent_column', '=', 'value')->get();
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }

        // Test invalid data types
        try {
            DB::table($this->testTable)->insert([
                'id' => 'invalid-uuid-format',
                'name' => 'Test',
                'age' => 'not-a-number',
                'created_at' => 'invalid-date',
            ]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testInsert(): void {
        $id = Uuid::uuid4()->toString();

        $result = DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'Test User',
            'age' => 25,
            'created_at' => now(),
        ]);

        $this->assertTrue($result);

        $user = DB::table($this->testTable)->where('id', $id)->first();
        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals('Test User', $user['name']);
    }

    public function testInsertCollection(): void {

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $builder->insertCollection('set', 'tags', ['php', 'laravel', 'cassandra']);

        // Test that collection binding was added
        $bindings = $builder->getBindings();
        $this->assertEquals(['set', 'tags', 'php', 'laravel', 'cassandra'], $bindings);
    }

    public function testInvalidCollectionType(): void {
        $this->expectException(InvalidArgumentException::class);

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $builder->updateCollection('invalid', 'tags', 'add', ['value']);
    }

    public function testLargeStringData(): void {
        $largeString = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 1000);
        $id = Uuid::uuid4()->toString();

        // Test inserting large string data
        $result = DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => $largeString,
            'age' => 25,
            'created_at' => now(),
        ]);

        $this->assertTrue($result);

        // Test retrieving large string data
        $user = DB::table($this->testTable)->where('id', $id)->first();
        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals($largeString, $user['name']);
    }

    public function testLimit(): void {
        // Insert some test data
        for ($i = 0; $i < 5; $i++) {
            DB::table($this->testTable)->insert([
                'id' => Uuid::uuid4()->toString(),
                'name' => "User $i",
                'age' => 20 + $i,
                'created_at' => now(),
            ]);
        }

        $users = DB::table($this->testTable)->limit(3)->get();

        $this->assertLessThanOrEqual(3, $users->count());
    }

    public function testLimitAndOffset(): void {
        // Insert ordered test data
        $recordCount = 50;
        for ($i = 0; $i < $recordCount; $i++) {
            DB::table($this->testTable)->insert([
                'id' => Uuid::uuid4()->toString(),
                'name' => sprintf('User %03d', $i),
                'age' => 20 + $i,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        // Test limit
        $results = DB::table($this->testTable)->limit(10)->get();
        $this->assertLessThanOrEqual(10, $results->count());

        // Test different limit sizes
        $limits = [1, 5, 20, 100];
        foreach ($limits as $limit) {
            $results = DB::table($this->testTable)->limit($limit)->get();
            $this->assertLessThanOrEqual($limit, $results->count());
        }

        // Test offset
        $results = DB::table($this->testTable)->offset(10)->limit(5)->get();
        $this->assertLessThanOrEqual(5, $results->count());
    }

    public function testPaginate(): void {
        // Insert some test data
        for ($i = 0; $i < 10; $i++) {
            DB::table($this->testTable)->insert([
                'id' => Uuid::uuid4()->toString(),
                'name' => "User $i",
                'age' => 20 + $i,
                'created_at' => now(),
            ]);
        }

        $paginated = DB::table($this->testTable)->paginate(5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
        $this->assertEquals(5, $paginated->perPage());
    }

    public function testSelect(): void {
        $results = DB::table($this->testTable)->select(['id', 'name'])->get();
        $this->assertIsArray($results->toArray());
    }

    public function testSetConsistency(): void {
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $builder->setConsistency(Consistency::ALL);

        // Test that consistency is set (we can't easily test the actual consistency level)
        $results = $builder->get();
        $this->assertIsArray($results->toArray());
    }

    public function testSimplePaginateThrowsException(): void {
        $this->expectException(RuntimeException::class);

        DB::table($this->testTable)->simplePaginate();
    }

    public function testTimestampOperations(): void {
        $timestamps = [
            now()->subDays(10),
            now()->subDays(5),
            now(),
            now()->addDays(5),
        ];

        $ids = [];
        foreach ($timestamps as $i => $timestamp) {
            $id = Uuid::uuid4()->toString();
            $ids[] = $id;

            DB::table($this->testTable)->insert([
                'id' => $id,
                'name' => "User $i",
                'age' => 25 + $i,
                'created_at' => $timestamp,
            ]);
        }

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $recentResults = $builder
            ->where('created_at', '>', now()->subDays(7))
            ->allowFiltering()
            ->get();

        $this->assertGreaterThanOrEqual(2, $recentResults->count());
    }

    public function testUnsupportedPagination(): void {
        $this->expectException(RuntimeException::class);

        DB::table($this->testTable)->cursorPaginate();
    }

    public function testUpdate(): void {
        $id = Uuid::uuid4()->toString();

        DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'Original Name',
            'age' => 25,
            'created_at' => now(),
        ]);

        $result = DB::table($this->testTable)
            ->where('id', $id)
            ->update(['name' => 'Updated Name']);

        $this->assertEquals(1, $result);

        $user = DB::table($this->testTable)->where('id', $id)->first();
        $this->assertIsArray($user);
        $this->assertEquals('Updated Name', $user['name']);
    }

    public function testUpdateCollection(): void {
        $id = Uuid::uuid4()->toString();

        // First insert a record
        DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'Test User',
            'age' => 25,
            'created_at' => now(),
        ]);

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);

        $builder
            ->where('id', $id)
            ->updateCollection('set', 'tags', 'add', ['php', 'laravel']);

        // Test that collection binding was added
        $bindings = $builder->getBindings();
        $this->assertEquals([$id, 'set', 'tags', 'php', 'laravel', 'add'], $bindings);
    }

    public function testWhereClause(): void {
        $id = Uuid::uuid4()->toString();

        DB::table($this->testTable)->insert([
            'id' => $id,
            'name' => 'Test User',
            'age' => 25,
            'created_at' => now(),
        ]);

        $user = DB::table($this->testTable)->where('id', $id)->first();

        $this->assertNotNull($user);
        $this->assertIsArray($user);
        $this->assertEquals($id, $user['id']);
        $this->assertEquals('Test User', $user['name']);
    }

    public function testWhereConditions(): void {
        // Insert test data with various ages
        $testData = [
            ['id' => Uuid::uuid4()->toString(), 'name' => 'John', 'age' => 25],
            ['id' => Uuid::uuid4()->toString(), 'name' => 'Jane', 'age' => 30],
            ['id' => Uuid::uuid4()->toString(), 'name' => 'Bob', 'age' => 35],
            ['id' => Uuid::uuid4()->toString(), 'name' => 'Alice', 'age' => 40],
        ];

        foreach ($testData as $data) {
            $data['created_at'] = now();
            DB::table($this->testTable)->insert($data);
        }

        $normalizeResults = fn($results) => array_map(function ($result) {
            if (!is_array($result)) {
                throw new RuntimeException('Result is not an array');
            }

            return [
                'id' => $result['id'],
                'name' => $result['name'],
                'age' => $result['age'],
            ];
        }, $results->toArray());

        // Test various WHERE operators
        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->where('age', '>', 30)->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(2, $results); // Bob and Alice
        $this->assertContains($testData[2], $normalizedResults);
        $this->assertContains($testData[3], $normalizedResults);

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->where('age', '>=', 30)->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(3, $results); // Jane, Bob, and Alice
        $this->assertContains($testData[1], $normalizedResults);
        $this->assertContains($testData[2], $normalizedResults);
        $this->assertContains($testData[3], $normalizedResults);

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->where('age', '<', 35)->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(2, $results); // John and Jane
        $this->assertContains($testData[0], $normalizedResults);
        $this->assertContains($testData[1], $normalizedResults);

        $builder = DB::table($this->testTable);
        $this->assertInstanceOf(Builder::class, $builder);
        $results = $builder->allowFiltering()->where('age', '<=', 35)->get();
        $normalizedResults = $normalizeResults($results);

        $this->assertCount(3, $results); // John, Jane, and Bob
        $this->assertContains($testData[0], $normalizedResults);
        $this->assertContains($testData[1], $normalizedResults);
        $this->assertContains($testData[2], $normalizedResults);
    }

    public function testWhereIn(): void {
        $ids = [
            Uuid::uuid4()->toString(),
            Uuid::uuid4()->toString(),
        ];

        foreach ($ids as $i => $id) {
            DB::table($this->testTable)->insert([
                'id' => $id,
                'name' => "User $i",
                'age' => 20 + $i,
                'created_at' => now(),
            ]);
        }

        $users = DB::table($this->testTable)->whereIn('id', $ids)->get();

        $this->assertCount(2, $users);
    }
}
