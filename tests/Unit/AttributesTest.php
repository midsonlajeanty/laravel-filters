<?php

declare(strict_types=1);

use Mds\LaravelFilters\Attributes\ArrayOf;
use Mds\LaravelFilters\Attributes\Cast;
use Mds\LaravelFilters\Attributes\DateFormat;
use Mds\LaravelFilters\Attributes\Delimiter;
use Mds\LaravelFilters\Attributes\MapInput;
use Mds\LaravelFilters\Attributes\MapTo;
use Mds\LaravelFilters\Attributes\QueryName;

it('instantiates attributes correctly', function (): void {
    $arrayOf = new ArrayOf('int');
    expect($arrayOf->type)->toBe('int');

    $cast = new Cast('SomeCaster');
    expect($cast->caster)->toBe('SomeCaster');

    $dateFormat = new DateFormat('Y-m-d');
    expect($dateFormat->format)->toBe('Y-m-d');

    $delimiter = new Delimiter('|');
    expect($delimiter->delimiter)->toBe('|');

    $mapInput = new MapInput('input_name');
    expect($mapInput->name)->toBe('input_name');

    $mapTo = new MapTo('db_column');
    expect($mapTo->column)->toBe('db_column');

    $queryName = new QueryName('query_name');
    expect($queryName->name)->toBe('query_name');
});
