<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Operators;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mds\LaravelFilters\Contracts\QueryOperator;

final readonly class ContainsOperator implements QueryOperator
{
    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, string $column, mixed $value): void
    {
        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valStr);
        $query->where($column, 'LIKE', '%'.$escaped.'%');
    }
}
