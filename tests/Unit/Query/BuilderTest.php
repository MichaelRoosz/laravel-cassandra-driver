<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Tests\Unit\Query;

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

    public function testAllowFiltering(): void {
        $builder = DB::table($this->testTable)->allowFiltering(); /** @phpstan-ignore method.notFound */

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertTrue($builder->allowFiltering);

        // Test with actual filtering
        $results = $builder->where('age', '>', 20)->get();
        $this->assertIsArray($results->toArray());
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

    public function testIgnoreWarnings(): void {
        $builder = DB::table($this->testTable)->ignoreWarnings(); /** @phpstan-ignore method.notFound */

        $this->assertInstanceOf(Builder::class, $builder);

        $results = $builder->get();
        $this->assertIsArray($results->toArray());
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
        $result = $builder->insertCollection('set', 'tags', ['php', 'laravel', 'cassandra']); /** @phpstan-ignore method.notFound */

        $this->assertInstanceOf(Builder::class, $result);

        // Test that collection binding was added
        $bindings = $builder->getBindings();
        $this->assertEquals(['set', 'tags', 'php', 'laravel', 'cassandra'], $bindings);
    }

    public function testInvalidCollectionType(): void {
        $this->expectException(InvalidArgumentException::class);

        DB::table($this->testTable)->updateCollection('invalid', 'tags', 'add', ['value']); /** @phpstan-ignore method.notFound */
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
        $builder = DB::table($this->testTable)->setConsistency(Consistency::ALL); /** @phpstan-ignore method.notFound */

        $this->assertInstanceOf(Builder::class, $builder);

        // Test that consistency is set (we can't easily test the actual consistency level)
        $results = $builder->get();
        $this->assertIsArray($results->toArray());
    }

    public function testSimplePaginateThrowsException(): void {
        $this->expectException(RuntimeException::class);

        DB::table($this->testTable)->simplePaginate();
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

        $builder = DB::table($this->testTable) /** @phpstan-ignore method.notFound */
            ->where('id', $id)
            ->updateCollection('set', 'tags', 'add', ['php', 'laravel']);

        $this->assertInstanceOf(Builder::class, $builder);

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
