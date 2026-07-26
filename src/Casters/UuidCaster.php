<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Illuminate\Http\Request;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionNamedType;

final readonly class UuidCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }

        $className = $type->getName();

        return $className === UuidInterface::class || $className === Uuid::class;
    }

    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if (empty($value)) {
            return null;
        }

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        if ($valStr !== '' && Uuid::isValid($valStr)) {
            return Uuid::fromString($valStr);
        }

        throw CastException::invalidValue('Uuid', $value);
    }
}
