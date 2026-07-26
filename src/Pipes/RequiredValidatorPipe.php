<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Pipes;

use Closure;
use Illuminate\Validation\ValidationException;
use Mds\LaravelFilters\Attributes\Required;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;

final readonly class RequiredValidatorPipe implements Pipe
{
    public function handle(PropertyContext $context, Closure $next): mixed
    {
        if ($context->isMissing && count($context->reflection->getAttributes(Required::class)) > 0) {
            throw ValidationException::withMessages([
                $context->inputName => "The {$context->inputName} filter is required.",
            ]);
        }

        return $next($context);
    }
}
