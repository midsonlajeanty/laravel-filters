<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mds\LaravelFilters\Attributes\ArrayOf;
use Mds\LaravelFilters\Attributes\Cast;
use Mds\LaravelFilters\Attributes\Delimiter;
use Mds\LaravelFilters\Attributes\MapInput;
use Mds\LaravelFilters\Attributes\Required;
use Mds\LaravelFilters\Casters\BuiltinCaster;
use Mds\LaravelFilters\Context\PropertyContext;
use Mds\LaravelFilters\Contracts\QueryCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use Mds\LaravelFilters\Exceptions\IgnoreParameterException;
use Mds\LaravelFilters\Pipes\ArrayExploderPipe;
use Mds\LaravelFilters\Pipes\CasterPipe;
use Mds\LaravelFilters\Pipes\RequiredValidatorPipe;
use Mds\LaravelFilters\Pipes\ResolveInputNamePipe;
use Mds\LaravelFilters\Pipes\ResolveValuePipe;

it('resolves input name', function (): void {
    $pipe = new ResolveInputNamePipe;
    $request = Request::create('/?custom_name=hello');

    $type = new ReflectionClass(new class
    {
        #[MapInput('custom_name')]
        public string $name;
    });

    $context = new PropertyContext($type->getProperty('name'), $request);
    $pipe->handle($context, fn ($c) => $c);

    expect($context->inputName)->toBe('custom_name');
});

it('resolves value', function (): void {
    $pipe = new ResolveValuePipe;
    $request = Request::create('/?name=hello');

    $type = new ReflectionClass(new class
    {
        public string $name;
    });

    $context = new PropertyContext($type->getProperty('name'), $request);
    $context->inputName = 'name';
    $pipe->handle($context, fn ($c) => $c);

    expect($context->value)->toBe('hello');
    expect($context->isMissing)->toBeFalse();
});

it('validates required', function (): void {
    $pipe = new RequiredValidatorPipe;
    $request = Request::create('/');

    $type = new ReflectionClass(new class
    {
        #[Required]
        public string $name;
    });

    $context = new PropertyContext($type->getProperty('name'), $request);
    $context->isMissing = true;

    expect(function () use ($pipe, $context): void {
        $pipe->handle($context, fn ($c) => $c);
    })->toThrow(ValidationException::class);
});

it('explodes array', function (): void {
    $pipe = new ArrayExploderPipe;
    $request = Request::create('/');

    $type = new ReflectionClass(new class
    {
        public array $tags;
    });

    $context = new PropertyContext($type->getProperty('tags'), $request);
    $context->value = 'a,b';

    $pipe->handle($context, fn ($c) => $c);

    expect($context->value)->toBe(['a', 'b']);
});

it('explodes array with custom delimiter', function (): void {
    $pipe = new ArrayExploderPipe;
    $request = Request::create('/');

    $type = new ReflectionClass(new class
    {
        #[Delimiter('|')]
        public array $tags;

        public array $configTags;

        public string|int $union;
    });

    $context = new PropertyContext($type->getProperty('tags'), $request);
    $context->value = 'a|b';
    $pipe->handle($context, fn ($c) => $c);
    expect($context->value)->toBe(['a', 'b']);

    $context2 = new PropertyContext($type->getProperty('union'), $request);
    $context2->value = 'a,b';
    $pipe->handle($context2, fn ($c) => $c);
    expect($context2->value)->toBe('a,b');

    // Invalid config
    config(['filters.delimiter' => 123]);
    $context3 = new PropertyContext($type->getProperty('configTags'), $request);
    $context3->value = 'a,b';
    $pipe->handle($context3, fn ($c) => $c);
    expect($context3->value)->toBe(['a', 'b']);
});

it('casts value', function (): void {
    $pipe = app(CasterPipe::class);
    $request = Request::create('/');

    $type = new ReflectionClass(new class
    {
        public int $age;
    });

    $context = new PropertyContext($type->getProperty('age'), $request);
    $context->value = '10';
    $pipe->handle($context, fn ($c) => $c);

    expect($context->value)->toBe(10);
});

it('casts array of custom objects', function (): void {
    $pipe = app(CasterPipe::class);
    $request = Request::create('/');

    $type = new ReflectionClass(new class
    {
        #[ArrayOf('int')]
        public array $ages;

        #[ArrayOf('int')]
        public Collection $scores;

        #[Cast(BuiltinCaster::class)]
        public string $explicit;

        public ?string $nullable;
    });

    $context = new PropertyContext($type->getProperty('ages'), $request);
    $context->value = ['1', '2'];
    $pipe->handle($context, fn ($c) => $c);
    expect($context->value)->toBe([1, 2]);

    $context2 = new PropertyContext($type->getProperty('scores'), $request);
    $context2->value = ['3', '4'];
    $pipe->handle($context2, fn ($c) => $c);
    expect($context2->value)->toBeInstanceOf(Collection::class);
    expect($context2->value->toArray())->toBe([3, 4]);

    $context3 = new PropertyContext($type->getProperty('explicit'), $request);
    $context3->value = 123;
    $pipe->handle($context3, fn ($c) => $c);
    expect($context3->value)->toBe('123');

    $context4 = new PropertyContext($type->getProperty('nullable'), $request);
    $context4->value = null;
    $pipe->handle($context4, fn ($c) => $c);
    expect($context4->value)->toBeNull();

    // union skips
    $typeUnion = new ReflectionClass(new class
    {
        public string|int $union;

        public array $noArrayOf;

        #[ArrayOf('int')]
        public ?array $nullableArray;

        public ?int $nullableInt;
    });

    $contextUnion = new PropertyContext($typeUnion->getProperty('union'), $request);
    $contextUnion->value = '10';
    $pipe->handle($contextUnion, fn ($c) => $c);

    $contextNoArrayOf = new PropertyContext($typeUnion->getProperty('noArrayOf'), $request);
    $contextNoArrayOf->value = ['10'];
    $pipe->handle($contextNoArrayOf, fn ($c) => $c);

    $contextNullableArray = new PropertyContext($typeUnion->getProperty('nullableArray'), $request);
    $contextNullableArray->value = null;
    $pipe->handle($contextNullableArray, fn ($c) => $c);

    $contextNullableInt = new PropertyContext($typeUnion->getProperty('nullableInt'), $request);
    $contextNullableInt->value = null;
    $pipe->handle($contextNullableInt, fn ($c) => $c);

    // Unsupported class
    $typeUnsupported = new ReflectionClass(new class
    {
        public UnsupportedClass $unsupported;
    });
    $contextUnsupported = new PropertyContext($typeUnsupported->getProperty('unsupported'), $request);
    $contextUnsupported->value = '123';
    $pipe->handle($contextUnsupported, fn ($c) => $c);
    expect($contextUnsupported->value)->toBe('123');
});

final class UnsupportedClass {}

it('handles ignore parameter exception', function (): void {
    $pipe = app(CasterPipe::class);
    $request = Request::create('/');

    final class ThrowIgnoreCaster implements QueryCaster
    {
        public function supports(ReflectionNamedType $type): bool
        {
            return true;
        }

        public function cast(ReflectionParameter|ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
        {
            throw new IgnoreParameterException;
        }
    }

    $type = new ReflectionClass(new class
    {
        #[Cast(ThrowIgnoreCaster::class)]
        public string $name;
    });

    $context = new PropertyContext($type->getProperty('name'), $request);
    $context->value = 'test';
    $pipe->handle($context, fn ($c) => $c);
    expect($context->isIgnored)->toBeTrue();
});

it('handles cast exception with different actions', function (): void {
    $pipe = app(CasterPipe::class);
    $request = Request::create('/');

    final class ThrowCastCaster implements QueryCaster
    {
        public function supports(ReflectionNamedType $type): bool
        {
            return true;
        }

        public function cast(ReflectionParameter|ReflectionProperty $reflection, ReflectionNamedType $type, mixed $value, Request $request): mixed
        {
            throw CastException::invalidValue('string', 'fail');
        }
    }

    $type = new ReflectionClass(new class
    {
        #[Cast(ThrowCastCaster::class)]
        public string $name;
    });

    $context = new PropertyContext($type->getProperty('name'), $request);
    $context->value = 'fail';
    $context->inputName = 'name';

    config(['filters.invalid_value_action' => 'ignore']);
    $pipe->handle($context, fn ($c) => $c);
    expect($context->isIgnored)->toBeTrue();

    $context2 = new PropertyContext($type->getProperty('name'), $request);
    $context2->value = 'fail';
    $context2->inputName = 'name';
    config(['filters.invalid_value_action' => 'null']);
    $pipe->handle($context2, fn ($c) => $c);
    expect($context2->value)->toBeNull();

    $context3 = new PropertyContext($type->getProperty('name'), $request);
    $context3->value = 'fail';
    $context3->inputName = 'name';
    config(['filters.invalid_value_action' => 'throw']);
    expect(function () use ($pipe, $context3): void {
        $pipe->handle($context3, fn ($c) => $c);
    })->toThrow(ValidationException::class);
});
