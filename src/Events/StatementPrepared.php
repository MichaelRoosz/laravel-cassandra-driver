<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Events;

use Cassandra\Response\Result;
use LaravelCassandraDriver\Connection;

class StatementPrepared {
    /**
     * The database connection instance.
     *
     * @var \LaravelCassandraDriver\Connection
     */
    public Connection $connection;

    /**
     * The CDO statement.
     */
    public Result $statement;

    /**
     * Create a new event instance.
     *
     * @param  \LaravelCassandraDriver\Connection $connection
     * @return void
     */
    public function __construct(Connection $connection, Result $statement) {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}
