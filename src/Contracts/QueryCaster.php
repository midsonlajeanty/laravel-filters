<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Contracts;

use Illuminate\Http\Request;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

interface QueryCaster
{
    /**
     * Determine if this caster supports the given type.
     */
    public function supports(ReflectionNamedType $type): bool;

    /**
     * Cast the raw value into the appropriate type.
     *
     * @param  mixed  $value  The raw value from the request.
     */
    public function cast(ReflectionParameter|ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed;
}
