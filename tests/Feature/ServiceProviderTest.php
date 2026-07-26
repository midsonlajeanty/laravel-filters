<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Mds\LaravelFilters\QueryFilter;

final class SpFilter extends QueryFilter
{
    public string $name;
}

it('resolves query filter from container', function (): void {
    app()->instance('request', Request::create('/?name=resolved'));

    $filter = app(SpFilter::class);

    expect($filter)->toBeInstanceOf(SpFilter::class);
    expect($filter->name)->toBe('resolved');
});
