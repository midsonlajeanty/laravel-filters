<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Mds\LaravelFilters\Attributes\ArrayOf;
use Mds\LaravelFilters\Attributes\Delimiter;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;

final readonly class ArrayExploderPipe implements Pipe
{
    public function handle(PropertyContext $context, Closure $next): mixed
    {
        if ($context->isMissing || ! is_string($context->value)) {
            return $next($context);
        }

        $type = $context->reflection->getType();

        if (! $type instanceof \ReflectionNamedType) {
            return $next($context);
        }

        $isArrayOf = count($context->reflection->getAttributes(ArrayOf::class)) > 0;
        $isCollectionType = in_array($type->getName(), ['array', Collection::class], true);

        if ($isArrayOf || $isCollectionType) {
            $delimiter = config('filters.delimiter', ',');

            if (! is_string($delimiter) || $delimiter === '') {
                $delimiter = ',';
            }

            $attributes = $context->reflection->getAttributes(Delimiter::class);

            if (count($attributes) > 0) {
                $attrDelimiter = $attributes[0]->newInstance()->delimiter;

                if ($attrDelimiter !== '') {
                    $delimiter = $attrDelimiter;
                }
            }

            $valStr = is_scalar($context->value) ? (string) $context->value : '';

            $context->value = explode($delimiter, $valStr);
        }

        return $next($context);
    }
}
