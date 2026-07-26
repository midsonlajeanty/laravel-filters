<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class QueryName
{
    public function __construct(public string $name) {}
}
