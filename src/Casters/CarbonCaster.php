<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Mds\LaravelFilters\Attributes\DateFormat;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use ReflectionNamedType;

final readonly class CarbonCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }

        $className = $type->getName();

        return is_a($className, \DateTimeInterface::class, true);
    }

    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if (empty($value)) {
            if ($type->allowsNull()) {
                return null;
            }

            throw CastException::invalidValue($type->getName(), $value);
        }

        $className = $type->getName();

        $attributes = $reflection->getAttributes(DateFormat::class);
        $format = count($attributes) > 0 ? $attributes[0]->newInstance()->format : null;

        $targetClass = match ($className) {
            \DateTimeInterface::class, \DateTime::class, Carbon::class => Carbon::class,
            \DateTimeImmutable::class, CarbonImmutable::class => CarbonImmutable::class,
            default => Carbon::class,
        };

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        if ($valStr === '') {
            throw CastException::invalidValue($targetClass, $value);
        }

        if ($format) {
            try {
                return $targetClass::createFromFormat($format, $valStr);
            } catch (\Exception) {
                throw CastException::invalidValue($targetClass, $value);
            }
        }

        try {
            return $targetClass::parse($valStr);
        } catch (\Exception) {
            throw CastException::invalidValue($targetClass, $value);
        }
    }
}
