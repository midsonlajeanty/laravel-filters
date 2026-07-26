<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Pipes;

use Closure;
use Mds\LaravelFilters\Attributes\MapInput;
use Mds\LaravelFilters\Attributes\QueryName;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;

final readonly class ResolveInputNamePipe implements Pipe
{
    public function handle(PropertyContext $context, Closure $next): mixed
    {
        $reflection = $context->reflection;
        $name = $reflection->getName();

        $attributes = $reflection->getAttributes(QueryName::class);

        if (count($attributes) > 0) {
            $name = $attributes[0]->newInstance()->name;
        } else {
            $attributes = $reflection->getAttributes(MapInput::class);

            if (count($attributes) > 0) {
                $name = $attributes[0]->newInstance()->name;
            }
        }

        $context->inputName = $name;

        return $next($context);
    }
}
