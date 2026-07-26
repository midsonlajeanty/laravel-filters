<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invalid Value Action
    |--------------------------------------------------------------------------
    |
    | When hydration fails (e.g., invalid enum value, bad date format),
    | this setting determines how the package behaves.
    |
    | Supported: 'throw', 'ignore', 'null'
    |
    */
    'invalid_value_action' => 'throw',

    /*
    |--------------------------------------------------------------------------
    | Operators
    |--------------------------------------------------------------------------
    |
    | Register the query operators that can be used within an associative
    | array filter. You can add your own custom operators here.
    |
    */
    'operators' => [
        'eq' => \Mds\LaravelFilters\Operators\EqualsOperator::class,
        'gt' => \Mds\LaravelFilters\Operators\GreaterThanOperator::class,
        'gte' => \Mds\LaravelFilters\Operators\GreaterThanOrEqualOperator::class,
        'lt' => \Mds\LaravelFilters\Operators\LessThanOperator::class,
        'lte' => \Mds\LaravelFilters\Operators\LessThanOrEqualOperator::class,
        'between' => \Mds\LaravelFilters\Operators\BetweenOperator::class,
        'in' => \Mds\LaravelFilters\Operators\InOperator::class,
        'contains' => \Mds\LaravelFilters\Operators\ContainsOperator::class,
        'starts' => \Mds\LaravelFilters\Operators\StartsOperator::class,
        'ends' => \Mds\LaravelFilters\Operators\EndsOperator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Casters
    |--------------------------------------------------------------------------
    |
    | Global casters that will attempt to cast a query string value to the
    | typehint of your QueryFilter properties.
    |
    */
    'casters' => array_filter([
        \Mds\LaravelFilters\Casters\BuiltinCaster::class,
        \Mds\LaravelFilters\Casters\EnumCaster::class,
        \Mds\LaravelFilters\Casters\CarbonCaster::class,
        class_exists(\Spatie\ModelStates\State::class) ? \Mds\LaravelFilters\Casters\StateCaster::class : null,
        \Mds\LaravelFilters\Casters\UuidCaster::class,
        \Mds\LaravelFilters\Casters\ValueObjectCaster::class,
    ]),

    /*
    |--------------------------------------------------------------------------
    | Default Array Delimiter
    |--------------------------------------------------------------------------
    |
    | This is the default string used to explode query string parameters into
    | an array when the property is type-hinted as an array. You can override
    | this on a per-property basis using the #[Delimiter('|')] attribute.
    |
    */
    'delimiter' => ',',
];
