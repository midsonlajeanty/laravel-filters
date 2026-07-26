<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Illuminate\Http\Request;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use ReflectionNamedType;

final readonly class ValueObjectCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin() || enum_exists($type->getName())) {
            return false;
        }

        $className = $type->getName();

        return class_exists($className) && (
            method_exists($className, 'tryFrom') ||
            method_exists($className, 'from') ||
            method_exists($className, 'parse') ||
            method_exists($className, 'fromString')
        );
    }

    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        $className = $type->getName();

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';

        try {
            if (method_exists($className, 'from')) {
                return $className::from($value);
            }
            if (method_exists($className, 'tryFrom')) {
                return $className::tryFrom($value);
            }
            if (method_exists($className, 'parse') && $valStr !== '') {
                return $className::parse($valStr);
            }
            if (method_exists($className, 'fromString') && $valStr !== '') {
                return $className::fromString($valStr);
            }
        } catch (\Throwable) {
            throw CastException::invalidValue($className, $value);
        }

        throw CastException::invalidValue($className, $value);
    }
}
