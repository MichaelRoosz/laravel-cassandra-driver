<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Schema;

use Closure;
use RuntimeException;

use Illuminate\Database\Schema\Builder as BaseBuilder;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use InvalidArgumentException;
use LaravelCassandraDriver\Connection;
use LaravelCassandraDriver\Consistency;

class Builder extends BaseBuilder {
    protected ?Consistency $consistency = null;

    protected bool $ignoreWarnings = false;

    /**
     * @inheritdoc
     */
    public function __construct(BaseConnection $connection) {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();

        /** @phpstan-ignore-next-line */
        $this->blueprintResolver(function (
            BaseConnection $connection,
            string $table,
            ?Closure $callback = null,
        ) {
            return new Blueprint($connection, $table, $callback);
        });
    }

    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name) {
        return $this->createKeyspace($name);
    }

    /**
     * Create a keyspace in the schema.
     *
     * @param  string  $name
     * @param  ?array<string,mixed>  $replication
     * @return bool
     */
    public function createKeyspace($name, $replication = null) {

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        return $this->connection->statement(
            $this->grammar->compileCreateKeyspace($name, $this->connection, $replication)
        );
    }

    /**
     * Create a keyspace in the schema if it does not exist.
     *
     * @param  string  $name
     * @param  ?array<string,mixed>  $replication
     * @return bool
     */
    public function createKeyspaceIfNotExists($name, $replication = null) {

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        return $this->connection->statement(
            $this->grammar->compileCreateKeyspace($name, $this->connection, $replication, true)
        );
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables() {
        $tables = array_column($this->getTables(), 'name');

        if (!$tables) {
            return;
        }

        foreach ($tables as $table) {
            $this->connection->statement('drop table if exists ' . $this->grammar->wrapTable($table));
        }
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name) {
        return $this->dropKeyspaceIfExists($name);
    }

    /**
     * Drop a keyspace from the schema if the keyspace exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropKeyspaceIfExists($name) {

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        return $this->connection->statement(
            $this->grammar->compileDropKeyspaceIfExists($name)
        );
    }

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     * @return list<array{name: string, type: string, type_name: string, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, generation: array{type: string, expression: string|null}|null}>
     */
    public function getColumns($table) {
        [$schema, $table] = $this->parseSchemaAndTable($table, withDefaultSchema: true);

        if ($schema === null) {
            throw new RuntimeException('Schema name is required.');
        }

        $table = $this->connection->getTablePrefix() . $table;

        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        /** @var list<array<string, mixed>> $results */
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileColumns(
                $schema,
                $table
            )
        );

        return $this->connection->getPostProcessor()->processColumns($results);
    }

    /**
     * Get the names of current schemas for the connection.
     *
     * @return string[]|null
     */
    public function getCurrentSchemaListing() {
        return [$this->connection->getDatabaseName()];
    }

    /**
     * Get the default schema name for the connection.
     *
     * @return string|null
     */
    public function getCurrentSchemaName() {
        return $this->getCurrentSchemaListing()[0] ?? null;
    }

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     * @return array<mixed>
     */
    public function getForeignKeys($table) {
        throw new RuntimeException('This database engine does not support foreign keys.');
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     * @return list<array{name: string, columns: list<string>, type: string, unique: bool, primary: bool}>
     */
    public function getIndexes($table) {
        [$schema, $table] = $this->parseSchemaAndTable($table, withDefaultSchema: true);

        if ($schema === null) {
            throw new RuntimeException('Schema name is required.');
        }

        $table = $this->connection->getTablePrefix() . $table;

        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        /** @var list<array<string, mixed>> $results */
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileIndexes(
                $schema,
                $table
            )
        );

        return $this->connection->getPostProcessor()->processIndexes($results);
    }

    /**
     * Get the tables that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return list<array{name: string, schema?: string|null, schema_qualified_name?: string, size?: int|null, comment?: string|null, collation?: string|null, engine?: string|null}>
     */
    public function getTables($schema = null) {

        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        $schema ??= $this->getCurrentSchemaName();
        if (!is_string($schema)) {
            throw new RuntimeException('Invalid schema name.');
        }

        /** @var list<array<string, mixed>> $results */
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileTables(
                $schema,
            )
        );

        return $this->connection->getPostProcessor()->processIndexes($results);
    }

    /**
     * Get the views that belong to the connection.
     *
     * @param  string|string[]|null  $schema
     * @return list<array{name: string, schema: string|null, schema_qualified_name: string, definition: string}>
     */
    public function getViews($schema = null) {
        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if (!$this->grammar instanceof Grammar) {
            throw new RuntimeException('Invalid grammar selected.');
        }

        $schema ??= $this->getCurrentSchemaName();
        if (!is_string($schema)) {
            throw new RuntimeException('Invalid schema name.');
        }

        /** @var list<array<string, mixed>> $results */
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileViews(
                $schema,
            )
        );

        return $this->connection->getPostProcessor()->processViews($results);
    }

    public function ignoreWarnings(bool $ignoreWarnings = true): self {
        $this->ignoreWarnings = $ignoreWarnings;

        return $this;
    }

    /**
     * Parse the given database object reference and extract the schema and table.
     *
     * @param  string  $reference
     * @param  string|bool|null  $withDefaultSchema
     * @return array<string|null>
     */
    public function parseSchemaAndTable($reference, $withDefaultSchema = null) {
        $segments = explode('.', $reference);

        if (count($segments) > 2) {
            throw new InvalidArgumentException(
                "Using three-part references is not supported, you may use `Schema::connection('{$segments[0]}')` instead."
            );
        }

        $table = $segments[1] ?? $segments[0];

        $schema = match (true) {
            isset($segments[1]) => $segments[0],
            is_string($withDefaultSchema) => $withDefaultSchema,
            $withDefaultSchema => $this->getCurrentSchemaName(),
            default => null,
        };

        return [$schema, $table];
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function rename($from, $to) {
        throw new RuntimeException('This database engine does not support renaming tables.');
    }

    public function setConsistency(Consistency $level): self {
        $this->consistency = $level;

        return $this;
    }

    protected function applyConsistency(): void {

        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if ($this->consistency) {
            $this->connection->setConsistency($this->consistency);
        } else {
            $this->connection->setDefaultConsistency();
        }
    }

    protected function applyIgnoreWarnings(): void {

        if (!$this->connection instanceof Connection) {
            throw new RuntimeException('Invalid connection selected.');
        }

        if ($this->ignoreWarnings) {
            $this->connection->ignoreWarnings();
        } else {
            $this->connection->logWarnings();
        }
    }

    protected function build(BaseBlueprint $blueprint) {
        $this->applyConsistency();
        $this->applyIgnoreWarnings();
        parent::build($blueprint);
    }
}
