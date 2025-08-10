<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Query;

use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class Grammar extends BaseGrammar {
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'wheres',
        'orders',
        'limit',
        'allowFiltering',
    ];

    public function __construct(BaseConnection $connection) {
        $this->setTablePrefix($connection->getTablePrefix());
        $this->setConnection($connection);
    }

    public function buildCollectionString(string $type, mixed $value): string {

        if (!is_array($value)) {
            throw new InvalidArgumentException('Collection values should be an array');
        }

        $isAssociative = false;
        if (count(array_filter(array_keys($value), 'is_string')) > 0) {
            $isAssociative = true;
        }

        if ('set' == $type || 'list' == $type) {
            $collection = collect($value)->map(
                function ($item, $key) {
                    return is_string($item) ? $this->quoteString($item) : $item;
                }
            )->implode(', ');
        } elseif ('map' == $type) {
            $collection = collect($value)->map(
                function ($item, $key) use ($isAssociative) {

                    if (!is_numeric($item) && !is_string($item)) {
                        throw new InvalidArgumentException('Map values should be numeric or string');
                    }

                    if ($isAssociative === true) {
                        $key = is_string($key) ? $this->quoteString($key) : $key;
                        $item = is_string($item) ? $this->quoteString($item) : $item;

                        return $key . ':' . $item;
                    } else {
                        return is_numeric($item) ? $item : $this->quoteString($item);
                    }
                }
            )->implode(', ');
        } else {

            throw new InvalidArgumentException('Invalid collection type');
        }

        return $collection;
    }

    /**
     * @param \Illuminate\Support\Collection<int,array{
     *   type: string,
     *   column: string,
     *   value: mixed
     * }> $collection
     */
    public function buildInsertCollectionParam(Collection $collection): string {
        return $collection->map(function (array $collectionItem) {
            return $this->compileCollectionValues($collectionItem['type'], $collectionItem['value']);
        })->implode(', ');
    }

    public function compileCollectionValues(string $type, mixed $value): string {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Collection values should be an array');
        }

        if ('set' == $type) {
            $collection = '{' . $this->buildCollectionString($type, $value) . '}';
        } elseif ('list' == $type) {
            $collection = '[' . $this->buildCollectionString($type, $value) . ']';
        } elseif ('map' == $type) {
            $collection = '{' . $this->buildCollectionString($type, $value) . '}';
        } else {
            throw new InvalidArgumentException('Invalid collection type');
        }

        return $collection;

    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $values
     * @return string
     */
    public function compileInsert(BaseBuilder $query, array $values) {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        //
        $query->bindings['insertCollection'] ??= [];

        /**
         * @var array<array{
         *   type: string,
         *   column: string,
         *   value: mixed
         * }> $insertCollection
         */
        $insertCollection = $query->bindings['insertCollection'];

        $insertCollections = collect($insertCollection);

        $insertCollectionArray = $insertCollections->mapWithKeys(function (array $collectionItem) {
            return [$collectionItem['column'] => $this->compileCollectionValues($collectionItem['type'], $collectionItem['value'])];
        })->all();
        //

        $subArray = reset($values);
        if (!is_array($subArray)) {
            throw new InvalidArgumentException('Insert values should be an array');
        }

        $columns = $this->columnize(array_keys($subArray));

        //
        $collectionColumns = $this->columnize(array_keys($insertCollectionArray));
        if ($collectionColumns) {
            $columns = $columns ? $columns . ', ' . $collectionColumns : $collectionColumns;
        }
        $collectionParam = $this->buildInsertCollectionParam($insertCollections);
        //

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same number of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function (mixed $record) {

            if (!is_array($record)) {
                throw new InvalidArgumentException('Record value should be an array');
            }

            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');

        //
        if ($collectionParam) {
            $parameters = $parameters ? $parameters . ', ' . $collectionParam : $collectionParam;
        }
        //

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile a select query into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(BaseBuilder $query) {
        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        $query->columns = $original;

        return $sql;
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $values
     * @return string
     */
    public function compileUpdate(BaseBuilder $query, array $values) {

        $table = $this->wrapTable($query->from);

        $columns = $this->compileUpdateColumns($query, $values);

        $where = $this->compileWheres($query);

        $upateCollections = $this->compileUpdateCollections($query);
        if ($upateCollections) {
            $upateCollections = $columns ? ', ' . $upateCollections : $upateCollections;
        }

        return "update {$table} set {$columns} {$upateCollections} {$where}";
    }

    public function compileUpdateCollections(BaseBuilder $query): string {
        $query->bindings['updateCollection'] ??= [];

        /**
         * @var array<array{
         *   type: string,
         *   column: string,
         *   value: mixed,
         *   operation: string|null
         * }> $updateCollection
         */
        $updateCollection = $query->bindings['updateCollection'];

        $updateCollections = collect($updateCollection);

        $updateCollectionCql = $updateCollections->map(
            function (array $collection) {
                if ($collection['operation']) {
                    return $collection['column'] . '=' . $collection['column'] . $collection['operation'] . $this->compileCollectionValues($collection['type'], $collection['value']);
                } else {
                    return $collection['column'] . '=' . $this->compileCollectionValues($collection['type'], $collection['value']);
                }
            }
        )->implode(', ');

        return $updateCollectionCql;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat() {
        return 'Y-m-d\\TH:i:sO';
    }

    /**
     * Quote the given string literal.
     *
     * @param  string|array<string>  $value
     * @return string
     */
    public function quoteString($value) {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param  string|null  $prefix
     * @return string
     */
    public function wrapTable($table, $prefix = null) {
        $table = parent::wrapTable($table);

        $keyspaceName = $this->connection->getDatabaseName();
        if ($keyspaceName) {
            $table = $this->wrapValue($keyspaceName) . '.' . $table;
        }

        return $table;
    }

    /**
     * Compile the allow filtering option into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileAllowFiltering(BaseBuilder $query, $value) {

        return $value ? 'allow filtering' : '';
    }

    /**
     * Compile the lock into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(BaseBuilder $query, $value) {
        return '';
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereBetween(BaseBuilder $query, $where) {
        throw new RuntimeException('whereBetween is not supported by Cassandra');
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereBetweenColumns(BaseBuilder $query, $where) {
        throw new RuntimeException('whereBetweenColumns is not supported by Cassandra');
    }

    /**
     * Compile a "where contains" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereContains(BaseBuilder $query, $where) {

        $where['operator'] = 'contains';

        return $this->whereBasic($query, $where);
    }

    /**
     * Compile a "where contains key" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereContainsKey(BaseBuilder $query, $where) {

        $where['operator'] = 'contains key';

        return $this->whereBasic($query, $where);
    }

    /**
     * Compile a "where like" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereLike(BaseBuilder $query, $where) {
        throw new RuntimeException('whereLike is not supported by Cassandra');
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereNotIn(BaseBuilder $query, $where) {
        throw new RuntimeException('whereNotIn is not supported by Cassandra');
    }

    /**
     * Compile a "where not in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereNotInRaw(BaseBuilder $query, $where) {
        throw new RuntimeException('whereNotInRaw is not supported by Cassandra');
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereNotNull(BaseBuilder $query, $where) {
        throw new RuntimeException('whereNotNull is not supported by Cassandra');
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $where
     * @return string
     */
    protected function whereNull(BaseBuilder $query, $where) {
        throw new RuntimeException('whereNull is not supported by Cassandra');
    }
}
