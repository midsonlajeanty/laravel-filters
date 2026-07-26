<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Illuminate\Http\Request;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use ReflectionNamedType;

final readonly class EnumCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }

        return enum_exists($type->getName());
    }

    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        /** @var class-string<\BackedEnum|\UnitEnum> $enumClass */
        $enumClass = $type->getName();

        if (is_subclass_of($enumClass, \BackedEnum::class)) {
            $val = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
            $enum = $enumClass::tryFrom($val) ?? (is_numeric($val) ? $enumClass::tryFrom((int) $val) : null);
            if (! $enum instanceof \BackedEnum) {
                throw CastException::invalidValue($enumClass, $value);
            }

            return $enum;
        }

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $valStr) {
                return $case;
            }
        }

        throw CastException::invalidValue($enumClass, $value);
    }
}
