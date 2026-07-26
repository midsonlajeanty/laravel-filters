<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Illuminate\Http\Request;
use Mds\LaravelFilters\Attributes\Delimiter;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use ReflectionNamedType;

final readonly class BuiltinCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        return $type->isBuiltin();
    }

    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        $delimiter = config('filters.delimiter', ',');
        if (! is_string($delimiter) || $delimiter === '') {
            $delimiter = ',';
        }

        $delimiterAttributes = $reflection->getAttributes(Delimiter::class);

        if (count($delimiterAttributes) > 0) {
            $attrDelim = $delimiterAttributes[0]->newInstance()->delimiter;

            if ($attrDelim !== '') {
                $delimiter = $attrDelim;
            }
        }

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';

        return match ($type->getName()) {
            'int' => is_numeric($value) ? (int) $value : throw CastException::invalidValue('int', $value),
            'float' => is_numeric($value) ? (float) $value : throw CastException::invalidValue('float', $value),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => $valStr,
            'array' => is_array($value) ? $value : explode($delimiter, $valStr),
            default => $value,
        };
    }
}
