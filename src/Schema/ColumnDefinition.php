<?php

declare(strict_types=1);

namespace LaravelCassandraDriver\Schema;

use Illuminate\Database\Schema\ColumnDefinition as BaseColumnDefinition;

/**
 * @method $this partition(string|null $name = null) Specify the parition key(s) for the table.
 * @method $this clustering(string|null $orderBy = null, string|null $name = null) Specify the clustering key(s) for the table.
 */
class ColumnDefinition extends BaseColumnDefinition {
}
