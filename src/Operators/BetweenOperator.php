<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Operators;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mds\LaravelFilters\Contracts\QueryOperator;

final readonly class BetweenOperator implements QueryOperator
{
    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, string $column, mixed $value): void
    {
        $values = is_array($value) ? array_values($value) : explode(',', is_scalar($value) || $value instanceof \Stringable ? (string) $value : '');

        if (count($values) !== 2) {
            return;
        }

        $query->whereBetween($column, $values);
    }
}
