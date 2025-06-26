<?php

declare(strict_types=1);

namespace LaravelCassandraDriver;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CassandraMigrationServiceProvider extends ServiceProvider implements DeferrableProvider {
    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides() {

        return [ 'migration.repository' ];
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->singleton('migration.repository', function ($app) {
            $migrations = $app['config']['database.migrations'];

            $table = is_array($migrations) ? ($migrations['table'] ?? null) : $migrations;

            return new CassandraMigrationRepository($app['db'], $table);
        });
    }
}
