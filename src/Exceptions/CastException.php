<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Exceptions;

use Exception;

final class CastException extends Exception
{
    public static function invalidValue(string $type, mixed $value): self
    {
        $val = is_scalar($value) ? (string) $value : gettype($value);

        return new self("Cannot cast value [{$val}] to type [{$type}].");
    }
}
