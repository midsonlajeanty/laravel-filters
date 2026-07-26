<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface QueryOperator
{
    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, string $column, mixed $value): void;
}
