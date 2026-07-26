<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Context;

use Illuminate\Http\Request;

final class PropertyContext
{
    public function __construct(
        public \ReflectionParameter|\ReflectionProperty $reflection,
        public Request $request,
        public string $inputName = '',
        public mixed $value = null,
        public bool $isIgnored = false,
        public bool $isMissing = false,
    ) {}
}
