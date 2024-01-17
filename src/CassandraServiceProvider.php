<?php

namespace LaraCassandra;

use Illuminate\Support\ServiceProvider;

class CassandraServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        require __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('cassandra', function ($config, $name) {
                $config['name'] = $name;

                return new Connection($config);
            });
        });
    }
}
