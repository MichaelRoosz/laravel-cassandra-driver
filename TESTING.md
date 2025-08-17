# Testing Guide

This document describes how to set up and run tests for the Laravel Cassandra Driver.

## Overview

The test suite includes:
- **Unit Tests**: Test individual components in isolation
- **Feature Tests**: Test full features with a real Cassandra database
- **Schema Tests**: Test table creation, modification, and deletion
- **Query Builder Tests**: Test CRUD operations and Cassandra-specific features
- **Migration Tests**: Test database migration functionality

## Prerequisites

- PHP 8.2 or higher
- Composer
- Docker and Docker Compose
- Cassandra 4.1+ (via Docker)

## Quick Start

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Start Cassandra:**
   ```bash
   docker-compose up -d
   ```

3. **Wait for Cassandra to be ready:**
   ```bash
   # Wait until this command succeeds
   docker exec cassandra-test cqlsh -e "describe keyspaces"
   ```

4. **Run tests:**
   ```bash
   composer test
   ```

## Test Structure

```
tests/
├── TestCase.php              # Base test case with Cassandra setup
├── Unit/                     # Unit tests
│   ├── Schema/
│   │   └── BlueprintTest.php # Test schema builder features
│   └── Query/
│       └── BuilderTest.php   # Test query builder features
├── Feature/                  # Feature tests
│   └── MigrationTest.php     # Test migration functionality
└── docker/
    └── init.cql              # Cassandra initialization script
```

## Configuration

### Environment Variables

The tests use the following environment variables:

```bash
DB_CONNECTION=cassandra
DB_HOST=127.0.0.1
DB_PORT=9042
DB_USERNAME=
DB_PASSWORD=
```

### PHPUnit Configuration

The `phpunit.xml` file configures:
- Test suites (Unit and Feature)
- Environment variables
- Code coverage settings
- Bootstrap file

## Test Categories

### Schema Tests (`tests/Unit/Schema/BlueprintTest.php`)

Tests Cassandra-specific schema features:
- **Partition Keys**: `->partition()` method
- **Clustering Columns**: `->clustering()` method with ordering
- **Data Types**: All Cassandra data types (UUID, text, int, etc.)
- **Collection Types**: Set, List, and Map collections
- **Constraints**: Testing unsupported operations throw exceptions

Example:
```php
public function testPartitionKeys(): void
{
    Schema::create('test_table', function (Blueprint $table) {
        $table->uuid('user_id')->partition();
        $table->uuid('post_id')->partition();
        $table->varchar('title');
    });
    
    $this->assertTrue(Schema::hasTable('test_table'));
}
```

### Query Builder Tests (`tests/Unit/Query/BuilderTest.php`)

Tests query operations:
- **CRUD Operations**: Insert, Select, Update, Delete
- **Consistency Levels**: `->setConsistency()` method
- **Allow Filtering**: `->allowFiltering()` for non-indexed queries
- **Collection Operations**: Update and insert collection data
- **Pagination**: Custom pagination for Cassandra
- **Warning Handling**: `->ignoreWarnings()` method

Example:
```php
public function testSetConsistency(): void
{
    $builder = DB::table('test_table')
        ->setConsistency(Consistency::ALL);
    
    $results = $builder->get();
    $this->assertIsArray($results->toArray());
}
```

### Migration Tests (`tests/Feature/MigrationTest.php`)

Tests migration functionality:
- **Repository Creation**: Migration table setup
- **Migration Logging**: Track executed migrations
- **Batch Operations**: Group migrations by batch
- **Complex Structures**: Test advanced table structures
- **Index Creation**: Secondary index management

Example:
```php
public function testCreateMigrationRepository(): void
{
    $this->repository->createRepository();
    $this->assertTrue(Schema::hasTable('migrations'));
}
```

## Running Tests

### All Tests
```bash
composer test
```

### Specific Test Suite
```bash
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
```

### Specific Test File
```bash
vendor/bin/phpunit tests/Unit/Schema/BlueprintTest.php
vendor/bin/phpunit tests/Feature/MigrationTest.php
```

### With Coverage
```bash
composer test-coverage
```

### Specific Test Method
```bash
vendor/bin/phpunit --filter testPartitionKeys
```

## Docker Setup

### Using Docker Compose (Recommended)

```bash
# Start Cassandra
docker-compose up -d

# View logs
docker-compose logs -f cassandra

# Stop and clean up
docker-compose down -v
```

### Manual Docker Setup

```bash
# Run Cassandra
docker run -d --name cassandra-test \
  -p 9042:9042 \
  -e CASSANDRA_CLUSTER_NAME=TestCluster \
  cassandra:4.1

# Initialize keyspace
docker exec cassandra-test cqlsh -f /path/to/init.cql
```

## Troubleshooting

### Cassandra Not Ready
If tests fail with connection errors:

```bash
# Check if Cassandra is running
docker ps

# Check Cassandra logs
docker logs cassandra-test

# Wait for Cassandra to be ready
timeout 300 bash -c 'until docker exec cassandra-test cqlsh -e "describe keyspaces"; do sleep 5; done'
```

### Test Database Cleanup
Tests automatically clean up created tables and keyspaces.

### Memory Issues
If you encounter memory issues:

```bash
# Increase Docker memory limit
# Or run fewer tests in parallel
vendor/bin/phpunit --process-isolation
```

## CI/CD Integration

The project includes GitHub Actions workflow (`.github/workflows/tests.yml`) that:
- Tests multiple PHP versions (8.2, 8.3)
- Tests multiple Cassandra versions (4.1, 5.0)
- Runs code quality checks (PHPStan, PHP CS Fixer)
- Generates coverage reports
- Includes integration tests

## Writing New Tests

### Unit Tests
Place in `tests/Unit/` directory. Extend `TestCase` class:

```php
<?php
namespace LaravelCassandraDriver\Test\Unit;

use LaravelCassandraDriver\Test\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        // Your test code
    }
}
```

### Intgration Tests
Place in `tests/Intgration/` directory. These tests use a real Cassandra connection:

```php
<?php
namespace LaravelCassandraDriver\Test\Intgration;

use LaravelCassandraDriver\Test\TestCase;
use Illuminate\Support\Facades\Schema;

class MyIntgrationTest extends TestCase
{
    public function testRealDatabaseOperation(): void
    {
        Schema::create('test_table', function ($table) {
            $table->uuid('id')->primary();
        });
        
        $this->assertTrue(Schema::hasTable('test_table'));
        
        // Clean up
        Schema::dropIfExists('test_table');
    }
}
```

## Performance Considerations

1. **Test Isolation**: Each test creates unique table names to avoid conflicts
2. **Cleanup**: Tests clean up created resources in tearDown methods
3. **Connection Reuse**: The TestCase class reuses database connections
4. **Parallel Execution**: Tests can run in parallel with proper isolation

## Best Practices

1. **Always clean up**: Drop tables created in tests
2. **Use unique names**: Use `uniqid()` for table names
3. **Test edge cases**: Test both success and failure scenarios
4. **Document complex tests**: Add comments for complex test logic
5. **Group related tests**: Use descriptive test method names 