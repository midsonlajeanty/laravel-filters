<?php

declare(strict_types=1);

namespace Tests;

use Mds\LaravelFilters\FilterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FilterServiceProvider::class,
        ];
    }
}
