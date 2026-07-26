<?php

declare(strict_types=1);

namespace Mds\LaravelFilters;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class FilterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filters.php' => config_path('filters.php'),
            ], 'laravel-filters-config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/vendor/laravel-filters'),
            ], 'laravel-filters-stubs');

            $this->commands([
                Console\Commands\MakeFilterCommand::class,
                Console\Commands\MakeCasterCommand::class,
                Console\Commands\MakeOperatorCommand::class,
            ]);
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filters.php', 'filters'
        );

        $this->app->singleton(FilterHydrator::class);

        $this->app->beforeResolving(QueryFilter::class, function (string $abstract, array $parameters, Application $app): void {
            if (! $app->bound($abstract)) {
                $app->bind($abstract, function (Application $app) use ($abstract) {
                    $request = $app->make('request');

                    /** @var class-string<QueryFilter> $abstract */
                    return $app->make(FilterHydrator::class)->hydrate($abstract, $request);
                });
            }
        });
    }
}
