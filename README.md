# **LaravelCassandraDriver**

A Laraval database driver for Cassandra.

## **Supported Laravel Versions**

- Laravel 11: supported via 11.x releases
- Laravel 12: currently not supported - will be supported with 12.x releases in the futrue

## **Installation**

Install using composer:
`composer require mroosz/laravel-cassandra-driver`

To support the Laravel database migration feature a custom migration service provider is needed:
- LaravelCassandraDriver\CassandraMigrationServiceProvider::class

It must be added at the very top of the service provider list so it can correctly override the default migration service provider.

## **Configuration**

Change your default database connection name in config/database.php:

    'default' => env('DB_CONNECTION', 'cassandra'),

And add a new cassandra connection:

    'cassandra' => [
        'driver' => 'cassandra',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 8082),
        'keyspace' => env('DB_DATABASE', 'cassandra_db'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'page_size'       => env('DB_PAGE_SIZE', 5000),
        'consistency'     => LaravelCassandraDriver\Consistency::LOCAL_ONE,
        'timeout'         => null,
        'connect_timeout' => 5.0,
        'request_timeout' => 12.0,
    ],

### .env Examples
```
  DB_CONNECTION=cassandra
  DB_HOST=127.0.0.1 
  DB_PORT=8082
```
or
```
  DB_CONNECTION=cassandra
  DB_HOST=172.198.1.1,172.198.1.2,172.198.1.3
  DB_PORT=8082,8082,7748

  DB_DATABASE=db_name
  
  DB_USERNAME=torecan
  DB_PASSWORD=***
  
  DB_PAGE_SIZE=500
```

### Supported Consistency Settings

  - LaravelCassandraDriver\Consistency::ALL
  - LaravelCassandraDriver\Consistency::ANY
  - LaravelCassandraDriver\Consistency::EACH_QUORUM
  - LaravelCassandraDriver\Consistency::LOCAL_ONE
  - LaravelCassandraDriver\Consistency::LOCAL_QUORUM
  - LaravelCassandraDriver\Consistency::LOCAL_SERIAL
  - LaravelCassandraDriver\Consistency::ONE
  - LaravelCassandraDriver\Consistency::TWO
  - LaravelCassandraDriver\Consistency::THREE
  - LaravelCassandraDriver\Consistency::QUORUM
  - LaravelCassandraDriver\Consistency::SERIAL

## **Schema**

Laravel migration features are supported (when LaravelCassandraDriver\CassandraMigrationServiceProvider is used):

  > php artisan migrate

  > php artisan make:migration createNewTable

## **Examples**
See
  - https://laravel.com/docs/11.x/database
  - https://laravel.com/docs/11.x/eloquent
 
Not all features are supported by Cassandra - those will throw exceptions when used.

Additionaly these features are supported by this driver:

- Schemas with Partition Keys and Clustering Columns:
 ```
    $table->int('bucket')->partition();
    $table->int('id')->partition();
    $table->int('userid')->partition();
    $table->int('join_date')->clustering('DESC');
    $table->int('update_date')->clustering('ASC');
    $table->int('another_date')->clustering();

    // Note: ->primary() is identical with partition()
 ```

- Connection and Builder classes support setting the query consistency via `setConsistency()`, for example:
  ```
    DB::table('example')->setConsistency(Consistency::ALL)->where('id', 1)->get();
  ```
- Builder classes support allow filtering via `allowFiltering()`, for example:
  ```
    DB::table('example')->where('time', '>=', 1)->allowFiltering()->get();
  ```

- By default warnings returned by Cassandra are logged - this can be turned off if needed:
  ```
    DB::table('example')->ignoreWarnings()->max('id');
  ```

## **Auth**

! TODO !

### This project is forked from https://github.com/cubettech/lacassa
