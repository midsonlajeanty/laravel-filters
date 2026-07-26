<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Traits;

use Illuminate\Database\Eloquent\Builder;
use Mds\LaravelFilters\QueryFilter;

trait Filterable
{
    /**
     * Apply the given filters to the query builder.
     */
    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }
}
