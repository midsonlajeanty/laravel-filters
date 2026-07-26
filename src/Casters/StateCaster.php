<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Casters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use ReflectionNamedType;
use Spatie\ModelStates\State;

final readonly class StateCaster implements QueryCaster
{
    public function supports(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin()) {
            return false;
        }

        // @codeCoverageIgnoreStart
        if (! class_exists(State::class)) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        return is_subclass_of($type->getName(), State::class);
    }

    /**
     * @return State<Model>|null
     *
     * @throws CastException
     */
    public function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
    {
        if ($value === null) {
            return null;
        }

        /** @var class-string<State<Model>> $stateClass */
        $stateClass = $type->getName();

        $valStr = is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        if ($valStr === '') {
            throw CastException::invalidValue($stateClass, $value);
        }

        try {
            /** @var array<class-string<State<Model>>> $stateClasses */
            $stateClasses = $stateClass::all()->toArray();

            foreach ($stateClasses as $class) {
                if ($class::getMorphClass() === $valStr) {
                    return new $class(null);
                }
            }

            throw CastException::invalidValue($stateClass, $value);
        } catch (\Throwable) {
            throw CastException::invalidValue($stateClass, $value);
        }
    }
}
