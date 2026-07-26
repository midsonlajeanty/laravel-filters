<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Sorts
{
    /**
     * @param  array<int, string>  $allowed  Whitelist of sortable column names. Empty means all columns are allowed.
     * @param  array<string, 'asc'|'desc'>  $default  Default sorts applied when no sorts are provided by the request.
     */
    public function __construct(
        public array $allowed = [],
        public array $default = [],
    ) {}
}
