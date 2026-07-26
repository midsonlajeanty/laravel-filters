<?php

declare(strict_types=1);

namespace Mds\LaravelFilters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\Pipe;
use ReflectionClass;
use ReflectionProperty;

final class FilterHydrator
{
    /**
     * @var array<class-string<Pipe>>
     */
    private array $pipes = [
        Pipes\ResolveInputNamePipe::class,
        Pipes\ResolveValuePipe::class,
        Pipes\RequiredValidatorPipe::class,
        Pipes\ArrayExploderPipe::class,
        Pipes\CasterPipe::class,
    ];

    /**
     * @var array<string, \ReflectionMethod|null>
     */
    private array $constructorCache = [];

    /**
     * Hydrate a QueryFilter instance from the Request.
     *
     * @template T of QueryFilter
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function hydrate(string $class, Request $request): QueryFilter
    {
        $reflection = new ReflectionClass($class);

        if (! array_key_exists($class, $this->constructorCache)) {
            $this->constructorCache[$class] = $reflection->getConstructor();
        }

        $constructor = $this->constructorCache[$class];

        if (! $constructor) {
            $instance = new $class;
            $this->hydrateProperties($instance, $reflection, $request);

            return $instance;
        }

        $arguments = [];
        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $context = new PropertyContext($parameter, $request);

            /** @var PropertyContext $context */
            $context = Pipeline::send($context)
                ->through($this->pipes)
                ->thenReturn();

            if ($context->isIgnored || $context->isMissing) {
                $arguments[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            } else {
                $arguments[] = $context->value;
            }
        }

        return new $class(...$arguments);
    }

    /**
     * @template T of QueryFilter
     *
     * @param  T  $instance
     * @param  ReflectionClass<T>  $reflection
     */
    private function hydrateProperties(object $instance, ReflectionClass $reflection, Request $request): void
    {
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {

            if ($property->isReadOnly()) {
                continue;
            }

            $context = new PropertyContext($property, $request);

            /** @var PropertyContext $context */
            $context = Pipeline::send($context)
                ->through($this->pipes)
                ->thenReturn();

            if (! $context->isIgnored && ! $context->isMissing) {
                $property->setValue($instance, $context->value);
            }
        }
    }
}
