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
    /** @var array<class-string, \ReflectionClass<QueryFilter>> */
    private static array $reflectionCache = [];

    /**
     * @return \ReflectionClass<static>
     */
    private function reflect(): \ReflectionClass
    {
        /** @var \ReflectionClass<static> */
        return self::$reflectionCache[static::class] ??= new \ReflectionClass(static::class);
    }

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
            fn (mixed $value): bool => $value !== null
        );

        $reflection = $this->reflect();

        foreach ($properties as $name => $value) {
            $methodName = 'filter'.ucfirst((string) $name);

            if (method_exists($this, $methodName)) {
                $this->{$methodName}($query, $value);

                continue;
            }

            $column = $name;
            $operator = null;

            if ($reflection->hasProperty($name)) {
                $propertyReflection = $reflection->getProperty($name);

                $sortsAttributes = $propertyReflection->getAttributes(Sorts::class);
                if (count($sortsAttributes) > 0) {
                    $sortsInstance = $sortsAttributes[0]->newInstance();
                    $this->applySorts($query, $value, $sortsInstance->allowed, $sortsInstance->default);

                    continue;
                }

                $mapToAttributes = $propertyReflection->getAttributes(MapTo::class);
                if (count($mapToAttributes) > 0) {
                    $mapTo = $mapToAttributes[0]->newInstance();
                    $column = $mapTo->column;
                    $operator = $mapTo->operator;
                }
            }

            if ($operator !== null) {
                $this->applyMappedOperator($query, $column, $operator, $value);

                continue;
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
     * @param  array<int, string>  $allowed
     * @param  array<string, 'asc'|'desc'>  $default
     */
    protected function applySorts(Builder $query, mixed $value, array $allowed = [], array $default = []): void
    {
        $sorts = is_array($value) ? $value : [$value];

        $validSorts = array_filter(
            $sorts,
            fn (mixed $s): bool => is_string($s) && $s !== ''
        );

        if ($validSorts === [] && $default !== []) {
            foreach ($default as $col => $dir) {
                if ($allowed !== [] && ! in_array($col, $allowed, true)) {
                    continue;
                }

                $query->orderBy($col, $dir);
            }

            return;
        }

        foreach ($validSorts as $sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            if ($allowed !== [] && ! in_array($column, $allowed, true)) {
                continue;
            }

            $query->orderBy($column, $direction);
        }
    }

    /**
     * Apply a single operator specified via #[MapTo] attribute.
     *
     * @param  Builder<Model>  $query
     */
    protected function applyMappedOperator(Builder $query, string $column, string $operator, mixed $value): void
    {
        /** @var array<string, class-string<QueryOperator>> $registeredOperators */
        $registeredOperators = config('filters.operators', []);

        if (isset($registeredOperators[$operator])) {
            /** @var QueryOperator $operatorInstance */
            $operatorInstance = app($registeredOperators[$operator]);
            $operatorInstance->apply($query, $column, $value);
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
        $reflection = $this->reflect();

        foreach ($reflection->getProperties() as $property) {
            if (count($property->getAttributes(Hidden::class)) > 0) {
                unset($properties[$property->getName()]);
            }
        }

        return $properties;
    }
}
