<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mds\LaravelFilters\Attributes\ArrayOf;
use Mds\LaravelFilters\Attributes\Cast;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use Mds\LaravelFilters\Exceptions\IgnoreParameterException;
use ReflectionNamedType;

final readonly class CasterPipe implements Pipe
{
    public function handle(PropertyContext $context, Closure $next): mixed
    {
        if ($context->isMissing || $context->isIgnored) {
            return $next($context);
        }

        $type = $context->reflection->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $next($context);
        }

        try {
            $context->value = $this->cast($context->reflection, $type, $context->value, $context);
        } catch (IgnoreParameterException) {
            $context->isIgnored = true;
        } catch (CastException $e) {
            $action = config('filters.invalid_value_action', 'throw');

            if ($action === 'ignore') {
                $context->isIgnored = true;
            } elseif ($action === 'null') {
                $context->value = null;
            } else {
                throw ValidationException::withMessages([
                    $context->inputName => $e->getMessage(),
                ]);
            }
        }

        return $next($context);
    }

    private function cast(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, PropertyContext $context): mixed
    {
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        $arrayOfAttributes = $reflection->getAttributes(ArrayOf::class);

        if (count($arrayOfAttributes) > 0 && is_array($value)) {

            $itemTypeClass = $arrayOfAttributes[0]->newInstance()->type;

            $fakeItemType = new class($itemTypeClass) extends ReflectionNamedType
            {
                public function __construct(private readonly string $typeName) {}

                public function getName(): string
                {
                    return $this->typeName;
                }

                public function isBuiltin(): bool
                {
                    return in_array($this->typeName, ['int', 'float', 'string', 'bool', 'array', 'iterable', 'mixed', 'object']);
                }

                // @codeCoverageIgnoreStart
                public function allowsNull(): bool
                {
                    return false;
                }

                public function __toString(): string
                {
                    return $this->typeName;
                }
                // @codeCoverageIgnoreEnd
            };

            $castedArray = array_map(
                fn (mixed $item): mixed => $this->castSingle($reflection, $fakeItemType, $item, $context),
                $value
            );

            return $type->getName() === Collection::class ? collect($castedArray) : $castedArray;
        }

        return $this->castSingle($reflection, $type, $value, $context);
    }

    private function castSingle(\ReflectionParameter|\ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, PropertyContext $context): mixed
    {

        $castAttributes = $reflection->getAttributes(Cast::class);

        if (count($castAttributes) > 0) {
            $casterClass = $castAttributes[0]->newInstance()->caster;

            /** @var QueryCaster $caster */
            $caster = app($casterClass);

            return $caster->cast($reflection, $type, $value, $context->request);
        }

        /** @var array<class-string<QueryCaster>> $casters */
        $casters = config('filters.casters', []);

        foreach ($casters as $casterClass) {
            /** @var QueryCaster $caster */
            $caster = app($casterClass);

            if ($caster->supports($type)) {
                return $caster->cast($reflection, $type, $value, $context->request);
            }
        }

        return $value;
    }
}
