<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Schema\Grammars\Grammar as BaseGrammar;
use LaravelCassandraDriver\LaravelCassandraException;

class Blueprint extends BaseBlueprint {
    /**
     * Add a new column to the blueprint.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array<mixed>  $parameters
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = []) {

        /** @var \LaravelCassandraDriver\Schema\ColumnDefinition $column */
        $column = $this->addColumnDefinition(new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        ));

        return $column;
    }
    /**
     * Create a new ascii column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function ascii($column) {
        return $this->addColumn('ascii', $column);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function bigIncrements($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new bigint column on the table.
     * 
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function bigint($column) {
        return $this->addColumn('bigint', $column);
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false) {
        return $this->addColumn('bigint', $column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @param  bool  $fixed
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function binary($column, $length = null, $fixed = false) {
        return $this->addColumn('blob', $column);
    }

    /**
     * Create a new blob column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function blob($column) {
        return $this->addColumn('blob', $column);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function boolean($column) {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function build(BaseConnection $connection, BaseGrammar $grammar) {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->unprepared($statement);
        }
    }

    /**
     * Create a new char column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function char($column, $length = null) {

        return $this->addColumn('varchar', $column);
    }

    /**
     * Specify the character set that should be used for the table.
     *
     * @param  string  $charset
     * @return void
     */
    public function charset($charset) {
        throw new LaravelCassandraException('This database driver does not support setting the charset.');
    }

    /**
     * Specify the clustering column(s) for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $orderBy
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function clustering($columns, $orderBy = null, $name = null) {

        if (!$orderBy) {
            $orderBy = 'ASC';
        } else {
            $orderBy = strtoupper($orderBy);
        }

        if (!in_array($orderBy, ['ASC', 'DESC'])) {
            throw new LaravelCassandraException('The order by clause must be either "ASC" or "DESC".');
        }

        /** @var \Illuminate\Database\Schema\IndexDefinition $index */
        $index = $this->indexCommand('clustering', $columns, $name ?? '', $orderBy);

        return $index;
    }

    /**
     * Specify the collation that should be used for the table.
     *
     * @param  string  $collation
     * @return void
     */
    public function collation($collation) {
        throw new LaravelCassandraException('This database driver does not support setting the collation.');
    }

    /**
     * Add a comment to the table.
     *
     * @param  string  $comment
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function comment($comment) {
        throw new LaravelCassandraException('This database driver does not support comments.');
    }

    /**
     * Create a new generated, computed column on the table.
     *
     * @param  string  $column
     * @param  string  $expression
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function computed($column, $expression) {
        throw new LaravelCassandraException('This database driver does not support computed columns.');
    }

    /**
     * Create a new counter column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function counter($column) {
        return $this->addColumn('counter', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function date($column) {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function dateTime($column, $precision = null) {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function dateTimeTz($column, $precision = null) {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string  $column
     * @param  int  $total
     * @param  int  $places
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function decimal($column, $total = 8, $places = 2) {
        return $this->addColumn('decimal', $column);
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string  $column
     * @return  \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function double($column) {
        return $this->addColumn('double', $column);
    }

    /**
     * Indicate that the given fulltext index should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropClustering($index) {
        throw new LaravelCassandraException('This database driver does not support dropping clustering indexes.');
    }

    /**
     * Indicate that the given column and foreign key should be dropped.
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropConstrainedForeignId($column) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropConstrainedForeignIdFor($model, $column = null) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropForeign($index) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropForeignIdFor($model, $column = null) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Indicate that the given fulltext index should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropFullText($index) {
        throw new LaravelCassandraException('This database driver does not support fulltext indexes.');
    }

    /**
     * Indicate that the given fulltext index should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropPartition($index) {
        throw new LaravelCassandraException('This database driver does not support dropping partition indexes.');
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array<mixed>|null  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropPrimary($index = null) {
        throw new LaravelCassandraException('This database driver does not support dropping a primary index.');
    }

    /**
     * Indicate that the given spatial index should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropSpatialIndex($index) {
        throw new LaravelCassandraException('This database driver does not support spatial indexes.');
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array<mixed>  $index
     * @return \Illuminate\Support\Fluent<string,mixed>
     */
    public function dropUnique($index) {
        throw new LaravelCassandraException('This database driver does not support unique indexes.');
    }

    /**
     * Create a new duration column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function duration($column) {
        return $this->addColumn('duration', $column);
    }

    /**
     * Specify the storage engine that should be used for the table.
     *
     * @param  string  $engine
     * @return void
     */
    public function engine($engine) {
        throw new LaravelCassandraException('This database driver does not support setting the storage engine.');
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string  $column
     * @param  array<mixed>  $allowed
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function enum($column, array $allowed) {
        throw new LaravelCassandraException('This database driver does not support the enum columns.');
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function float($column, $precision = 53) {
        return $this->addColumn('float', $column);
    }
    /**
     * Specify a foreign key for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\ForeignKeyDefinition
     */
    public function foreign($columns, $name = null) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignId($column) {
        throw new LaravelCassandraException('This database driver does not support foreign ids.');
    }

    /**
     * Create a foreign ID column for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  string|null  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignIdFor($model, $column = null) {
        throw new LaravelCassandraException('This database driver does not support foreign ids.');
    }

    /**
     * Create a new ULID column on the table with a foreign key constraint.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignUlid($column, $length = 26) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Create a new UUID column on the table with a foreign key constraint.
     *
     * @param  string  $column
     * @return \Illuminate\Database\Schema\ForeignIdColumnDefinition
     */
    public function foreignUuid($column) {
        throw new LaravelCassandraException('This database driver does not support foreign keys.');
    }

    /**
     * Create a new frozen column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function frozen($column) {
        return $this->addColumn('frozen', $column);
    }
    /**
     * Specify an fulltext for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function fullText($columns, $name = null, $algorithm = null) {
        throw new LaravelCassandraException('This database driver does not support fulltext indexes.');
    }

    /**
     * Create a new geography column on the table.
     *
     * @param  string  $column
     * @param  string|null  $subtype
     * @param  int  $srid
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function geography($column, $subtype = null, $srid = 4326) {
        throw new LaravelCassandraException('This database driver does not support geography columns.');
    }

    /**
     * Create a new geometry column on the table.
     *
     * @param  string  $column
     * @param  string|null  $subtype
     * @param  int  $srid
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function geometry($column, $subtype = null, $srid = 0) {
        throw new LaravelCassandraException('This database driver does not support geometry columns.');
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function id($column = 'id') {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }
    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function increments($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new inet column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function inet($column) {
        return $this->addColumn('inet', $column);
    }

    /**
     * Specify that the InnoDB storage engine should be used for the table (MySQL only).
     *
     * @return void
     */
    public function innoDb() {
        throw new LaravelCassandraException('This database driver does not support setting the storage engine.');
    }

    /**
     * Create a new int column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function int($column) {
        return $this->addColumn('int', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false) {
        return $this->addColumn('int', $column);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function integerIncrements($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
      * Create a new IP address column on the table.
      *
      * @param  string  $column
      * @return \LaravelCassandraDriver\Schema\ColumnDefinition
      */
    public function ipAddress($column = 'ip_address') {
        return $this->addColumn('inet', $column);
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function json($column) {
        throw new LaravelCassandraException('This database driver does not support json columns.');
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function jsonb($column) {
        throw new LaravelCassandraException('This database driver does not support jsonb columns.');
    }

    /**
     * Create a new list column on the table.
     *
     * @param string $column
     * @param string $collectionType
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function list($column, $collectionType) {
        return $this->addColumn('list', $column, compact('collectionType'));
    }

    /**
     * Create a new list column on the table.
     *
     * @param string $column
     * @param string $collectionType
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function listCollection($column, $collectionType) {
        return $this->addColumn('list', $column, compact('collectionType'));
    }

    /**
     * Create a new long text column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function longText($column) {
        return $this->addColumn('varchar', $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function macAddress($column = 'mac_address') {
        return $this->text($column);
    }

    /**
     * Create a new map column on the table.
     *
     * @param string $column
     * @param string $collectionType1
     * @param string $collectionType2
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function mapCollection($column, $collectionType1, $collectionType2) {
        return $this->addColumn('map', $column, compact('collectionType1', 'collectionType2'));
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function mediumIncrements($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false) {
        throw new LaravelCassandraException('This database driver does not support medium integer (3-byte) columns.');
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function mediumText($column) {
        return $this->addColumn('varchar', $column);
    }

    /**
     * Specify the parition key(s) for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function partition($columns, $name = null, $algorithm = null) {

        /** @var \Illuminate\Database\Schema\IndexDefinition $index */
        $index = $this->indexCommand('partition', $columns, $name ?? '', $algorithm);

        return $index;
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function primary($columns, $name = null, $algorithm = null) {

        /** @var \Illuminate\Database\Schema\IndexDefinition $index */
        $index = $this->indexCommand('partition', $columns, $name ?? '', $algorithm);

        return $index;
    }

    /**
     * Rename the table to a given name.
     *
     * @param  string  $to
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function rename($to) {
        throw new LaravelCassandraException('This database driver does not support renaming tables.');
    }

    /**
     * Indicate that the given indexes should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function renameIndex($from, $to) {
        throw new LaravelCassandraException('This database driver does not renaming indexes.');
    }

    /**
     * Create a new set column on the table.
     *
     * @param  string  $column
     * @param  array<mixed>  $allowed
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function set($column, array $allowed) {
        throw new LaravelCassandraException('This database driver does not support set columns. You can use setCollection instead.');
    }

    /**
     * Create a new set column on the table.
     *
     * @param string $column
     * @param string $collectionType
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function setCollection($column, $collectionType) {
        return $this->addColumn('set', $column, compact('collectionType'));
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function smallIncrements($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new smallint column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function smallint($column) {
        return $this->addColumn('smallint', $column);
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false) {
        return $this->addColumn('smallint', $column);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function spatialIndex($columns, $name = null) {
        throw new LaravelCassandraException('This database driver does not support spatial indexes.');
    }

    /**
     * Create a new string column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function string($column, $length = null) {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('varchar', $column);
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return void
     */
    public function temporary() {
        throw new LaravelCassandraException('This database driver does not support temporary tables.');
    }

    /**
     * Create a new text column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function text($column) {
        return $this->addColumn('varchar', $column);
    }

    /**
     * Create a new time column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function time($column, $precision = null) {
        return $this->addColumn('time', $column);
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function timestamp($column, $precision = null) {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function timestampTz($column, $precision = null) {
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * @param  string  $column
     * @param  int|null  $precision
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function timeTz($column, $precision = null) {
        return $this->addColumn('time', $column);
    }

    /**
     * Create a new timeuuid column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function timeuuid($column) {
        return $this->addColumn('timeuuid', $column);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function tinyIncrements($column) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new tinyint column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function tinyint($column) {
        return $this->addColumn('tinyint', $column);
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false) {
        return $this->addColumn('tinyint', $column);
    }

    /**
     * Create a new tiny text column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function tinyText($column) {
        return $this->addColumn('varchar', $column);
    }

    /**
     * Create a new tuple column on the table.
     *
     * @param string $column
     * @param string $tuple1type
     * @param string $tuple2type
     * @param string $tuple3type
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function tuple($column, $tuple1type, $tuple2type, $tuple3type) {
        return $this->addColumn('tuple', $column, compact('tuple1type', 'tuple2type', 'tuple3type'));
    }

    /**
     * Create a new ULID column on the table.
     *
     * @param  string  $column
     * @param  int|null  $length
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function ulid($column = 'ulid', $length = 26) {
        return $this->char($column, $length);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array<mixed>  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function unique($columns, $name = null, $algorithm = null) {
        throw new LaravelCassandraException('This database driver does not support unique indexes.');
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function unsignedBigInteger($column, $autoIncrement = false) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function unsignedInteger($column, $autoIncrement = false) {
        throw new LaravelCassandraException('This database driver does not support unsigned integer columns.');
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function unsignedMediumInteger($column, $autoIncrement = false) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function unsignedSmallInteger($column, $autoIncrement = false) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function unsignedTinyInteger($column, $autoIncrement = false) {
        throw new LaravelCassandraException('This database driver does not support auto-increment columns.');
    }

    /**
     * Create a new UUID column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function uuid($column = 'uuid') {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new varchar column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function varchar($column) {
        return $this->addColumn('varchar', $column);
    }

    /**
     * Create a new varint column on the table.
     *
     * @param string $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function varint($column) {
        return $this->addColumn('varint', $column);
    }

    /**
     * Create a new vector column on the table.
     *
     * @param  string  $column
     * @param  int|null  $dimensions
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function vector($column, $dimensions = null) {
        throw new LaravelCassandraException('This database driver does not support vector columns. You can use setCollection instead.');
    }
    /**
     * Create a new year column on the table.
     *
     * @param  string  $column
     * @return \LaravelCassandraDriver\Schema\ColumnDefinition
     */
    public function year($column) {
        return $this->addColumn('date', $column);
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addFluentIndexes(BaseConnection $connection, BaseGrammar $grammar) {
        foreach ($this->columns as $column) {
            foreach (['partition', 'clustering', 'primary', 'unique', 'index', 'fulltext', 'fullText', 'spatialIndex'] as $index) {

                if (!isset($column->{$index})) {
                    continue;
                }

                $columnName = isset($column->name) ? $column->name : null;

                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                if ($column->{$index} === true) {
                    $this->{$index}($columnName);
                    $column->{$index} = null;

                    continue 2;
                }

                // If the index has been specified on the given column, but it equals false
                // and the column is supposed to be changed, we will call the drop index
                // method with an array of column to drop it by its conventional name.
                elseif ($column->{$index} === false && !empty($column->change)) {
                    $this->{'drop' . ucfirst($index)}([$columnName]);
                    $column->{$index} = null;

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
                elseif (isset($column->{$index})) {
                    $this->{$index}($columnName, $column->{$index});
                    $column->{$index} = null;

                    continue 2;
                }
            }
        }
    }
}
