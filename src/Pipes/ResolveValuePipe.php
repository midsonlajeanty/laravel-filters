<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Pipes;

use Closure;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;

final readonly class ResolveValuePipe implements Pipe
{
    public function handle(PropertyContext $context, Closure $next): mixed
    {
        if (! $context->request->has($context->inputName)) {
            $context->isMissing = true;

            return $next($context);
        }

        $context->value = $context->request->input($context->inputName);

        return $next($context);
    }
}
