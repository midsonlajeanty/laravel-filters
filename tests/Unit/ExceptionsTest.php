<?php

declare(strict_types=1);

use Mds\LaravelFilters\Exceptions\CastException;

it('creates cast exception', function (): void {
    $e = new CastException('failed');
    expect($e->getMessage())->toBe('failed');
});
