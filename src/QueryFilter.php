<?php

declare(strict_types=1);

namespace Mds\LaravelFilters;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mds\LaravelFilters\Attributes\Hidden;
use Mds\LaravelFilters\Attributes\MapTo;
use Mds\LaravelFilters\Attributes\Sorts;
use Mds\LaravelFilters\Contracts\QueryOperator;

abstract class QueryFilter
{
    /**
     * Apply the filters to the given query builder.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query): Builder
    {
        $properties = array_filter(
            get_object_vars($this),
            fn ($value): bool => $value !== null
        );

        $reflection = new \ReflectionClass($this);

        foreach ($properties as $name => $value) {
            $methodName = 'filter'.ucfirst((string) $name);

            if (method_exists($this, $methodName)) {
                $this->{$methodName}($query, $value);

                continue;
            }

            $column = $name;
            if ($reflection->hasProperty($name)) {
                $propertyReflection = $reflection->getProperty($name);

                if (count($propertyReflection->getAttributes(Sorts::class)) > 0) {
                    $this->applySorts($query, $value);

                    continue;
                }

                $mapToAttributes = $propertyReflection->getAttributes(MapTo::class);
                if (count($mapToAttributes) > 0) {
                    $column = $mapToAttributes[0]->newInstance()->column;
                }
            }

            if (is_array($value) && Arr::isAssoc($value)) {
                /** @var array<string, mixed> $value */
                $this->applyOperators($query, $column, $value);

                continue;
            }

            if (is_scalar($value) || $value instanceof \BackedEnum || $value instanceof \UnitEnum) {
                $query->where($column, $value instanceof \BackedEnum ? $value->value : ($value instanceof \UnitEnum ? $value->name : $value));
            } elseif (is_array($value)) {
                $query->whereIn($column, $value);
            } elseif ($value instanceof Collection) {
                $query->whereIn($column, $value->toArray());
            } elseif ($value instanceof CarbonInterface) {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applySorts(Builder $query, mixed $value): void
    {
        $sorts = is_array($value) ? $value : [$value];

        foreach ($sorts as $sort) {
            if (! is_string($sort) || $sort === '') {
                continue;
            }

            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            $query->orderBy($column, $direction);
        }
    }

    /**
     * Apply advanced query operators.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $operators
     */
    protected function applyOperators(Builder $query, string $column, array $operators): void
    {
        /** @var array<string, class-string<QueryOperator>> $registeredOperators */
        $registeredOperators = config('filters.operators', []);

        foreach ($operators as $operator => $value) {
            if (isset($registeredOperators[$operator])) {
                /** @var QueryOperator $operatorInstance */
                $operatorInstance = app($registeredOperators[$operator]);
                $operatorInstance->apply($query, $column, $value);
            }
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var array<string, mixed> $properties */
        $properties = get_object_vars($this);
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            if (count($property->getAttributes(Hidden::class)) > 0) {
                unset($properties[$property->getName()]);
            }
        }

        return $properties;
    }
}
