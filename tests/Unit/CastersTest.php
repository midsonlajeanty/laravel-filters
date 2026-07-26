<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mds\LaravelFilters\Attributes\DateFormat;
use Mds\LaravelFilters\Attributes\Delimiter;
use Mds\LaravelFilters\Casters\BuiltinCaster;
use Mds\LaravelFilters\Casters\CarbonCaster;
use Mds\LaravelFilters\Casters\EnumCaster;
use Mds\LaravelFilters\Casters\StateCaster;
use Mds\LaravelFilters\Casters\UuidCaster;
use Mds\LaravelFilters\Casters\ValueObjectCaster;
use Mds\LaravelFilters\Exceptions\CastException;
use Ramsey\Uuid\UuidInterface;
use Spatie\ModelStates\State;

it('casts builtin types', function (): void {
    $caster = new BuiltinCaster;
    $request = Request::create('/');
    $type = new ReflectionClass(new class
    {
        public int $i;

        public float $f;

        public bool $b;

        public array $a;

        public string $s;
    });

    expect($caster->supports($type->getProperty('i')->getType()))->toBeTrue();
    expect($caster->cast($type->getProperty('i'), $type->getProperty('i')->getType(), '123', $request))->toBe(123);

    expect($caster->cast($type->getProperty('f'), $type->getProperty('f')->getType(), '12.3', $request))->toBe(12.3);

    expect($caster->cast($type->getProperty('b'), $type->getProperty('b')->getType(), 'true', $request))->toBeTrue();
    expect($caster->cast($type->getProperty('b'), $type->getProperty('b')->getType(), 'false', $request))->toBeFalse();

    expect($caster->cast($type->getProperty('s'), $type->getProperty('s')->getType(), 123, $request))->toBe('123');
    expect($caster->cast($type->getProperty('a'), $type->getProperty('a')->getType(), 'a,b', $request))->toBe(['a', 'b']);

    // Test null value
    $typeNullable = new ReflectionClass(new class
    {
        public ?int $nullableInt;
    });
    expect($caster->cast($typeNullable->getProperty('nullableInt'), $typeNullable->getProperty('nullableInt')->getType(), null, $request))->toBeNull();

    // Test Delimiter attribute
    $typeDelim = new ReflectionClass(new class
    {
        #[Delimiter('|')]
        public array $delimited;

        #[Delimiter('')]
        public array $emptyDelimited;
    });
    expect($caster->cast($typeDelim->getProperty('delimited'), $typeDelim->getProperty('delimited')->getType(), 'a|b', $request))->toBe(['a', 'b']);

    // Test invalid config delimiter
    config(['filters.delimiter' => 123]);
    expect($caster->cast($type->getProperty('a'), $type->getProperty('a')->getType(), 'a,b', $request))->toBe(['a', 'b']);
});

it('casts carbon', function (): void {
    $caster = new CarbonCaster;
    $request = Request::create('/');
    $type = new ReflectionClass(new class
    {
        public Carbon $date;

        public CarbonImmutable $dateImmutable;

        public ?Carbon $nullableDate;

        #[DateFormat('d/m/Y')]
        public Carbon $formattedDate;

        public int $builtin;
    });

    expect($caster->supports($type->getProperty('builtin')->getType()))->toBeFalse();
    expect($caster->supports($type->getProperty('date')->getType()))->toBeTrue();

    expect($caster->cast($type->getProperty('nullableDate'), $type->getProperty('nullableDate')->getType(), null, $request))->toBeNull();

    $date = $caster->cast($type->getProperty('date'), $type->getProperty('date')->getType(), '2023-01-01', $request);
    expect($date)->toBeInstanceOf(Carbon::class);

    $dateImm = $caster->cast($type->getProperty('dateImmutable'), $type->getProperty('dateImmutable')->getType(), '2023-01-01', $request);
    expect($dateImm)->toBeInstanceOf(CarbonImmutable::class);

    $formatted = $caster->cast($type->getProperty('formattedDate'), $type->getProperty('formattedDate')->getType(), '31/12/2023', $request);
    expect($formatted->format('Y-m-d'))->toBe('2023-12-31');

    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('date'), $type->getProperty('date')->getType(), 'invalid-date', $request);
    })->toThrow(CastException::class);

    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('formattedDate'), $type->getProperty('formattedDate')->getType(), 'invalid', $request);
    })->toThrow(CastException::class);

    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('date'), $type->getProperty('date')->getType(), ['invalid'], $request);
    })->toThrow(CastException::class);

    // Test custom DateTimeInterface
    $typeCustom = new ReflectionClass(new class
    {
        public MyCustomDateTime $customDate;
    });
    $custom = $caster->cast($typeCustom->getProperty('customDate'), $typeCustom->getProperty('customDate')->getType(), '2023-01-01', $request);
    expect($custom)->toBeInstanceOf(Carbon::class);
});

final class MyCustomDateTime extends DateTime {}

enum TestEnum: string
{
    case A = 'a';
}

enum TestUnitEnum
{
    case A;
    case B;
}

it('casts enum', function (): void {
    $caster = new EnumCaster;
    $request = Request::create('/');
    $typeNullable = new ReflectionClass(new class
    {
        public ?TestEnum $nullableEnum;

        public int $builtin;

        public TestUnitEnum $unitEnum;
    });

    expect($caster->supports($typeNullable->getProperty('builtin')->getType()))->toBeFalse();
    expect($caster->cast($typeNullable->getProperty('nullableEnum'), $typeNullable->getProperty('nullableEnum')->getType(), null, $request))->toBeNull();

    $type = new ReflectionClass(new class
    {
        public TestEnum $enum;
    });

    expect($caster->supports($type->getProperty('enum')->getType()))->toBeTrue();
    expect($caster->cast($type->getProperty('enum'), $type->getProperty('enum')->getType(), 'a', $request))->toBe(TestEnum::A);

    // BackedEnum error
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('enum'), $type->getProperty('enum')->getType(), 'invalid', $request);
    })->toThrow(CastException::class);

    // UnitEnum test
    expect($caster->cast($typeNullable->getProperty('unitEnum'), $typeNullable->getProperty('unitEnum')->getType(), 'A', $request))->toBe(TestUnitEnum::A);

    // UnitEnum error
    expect(function () use ($caster, $typeNullable, $request): void {
        $caster->cast($typeNullable->getProperty('unitEnum'), $typeNullable->getProperty('unitEnum')->getType(), 'invalid', $request);
    })->toThrow(CastException::class);
});

it('casts uuid', function (): void {
    $caster = new UuidCaster;
    $request = Request::create('/');
    $type = new ReflectionClass(new class
    {
        public UuidInterface $uuid;

        public int $builtin;

        public ?UuidInterface $nullableUuid;
    });

    expect($caster->supports($type->getProperty('builtin')->getType()))->toBeFalse();
    expect($caster->cast($type->getProperty('nullableUuid'), $type->getProperty('nullableUuid')->getType(), null, $request))->toBeNull();

    expect($caster->supports($type->getProperty('uuid')->getType()))->toBeTrue();
    $uuid = $caster->cast($type->getProperty('uuid'), $type->getProperty('uuid')->getType(), 'd7515fc2-d1cc-4e0f-90db-2b509bc97858', $request);
    expect($uuid)->toBeInstanceOf(UuidInterface::class);

    // Test exception
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('uuid'), $type->getProperty('uuid')->getType(), 'invalid-uuid', $request);
    })->toThrow(CastException::class);
});

abstract class MyDummyState extends State
{
    #[Override]
    public static function all(): Collection
    {
        return collect([MyActiveState::class]);
    }
}
final class MyActiveState extends MyDummyState
{
    public static $name = 'active';

    #[Override]
    public static function getMorphClass(): string
    {
        return 'active';
    }
}

it('casts state', function (): void {
    $caster = new StateCaster;
    $request = Request::create('/');
    $type = new ReflectionClass(new class
    {
        public MyDummyState $state;

        public int $builtin;

        public ?MyDummyState $nullableState;
    });

    expect($caster->supports($type->getProperty('builtin')->getType()))->toBeFalse();
    expect($caster->cast($type->getProperty('nullableState'), $type->getProperty('nullableState')->getType(), null, $request))->toBeNull();
    expect($caster->supports($type->getProperty('state')->getType()))->toBeTrue();

    // Test exception
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('state'), $type->getProperty('state')->getType(), 'invalid', $request);
    })->toThrow(CastException::class);

    // Test success
    $state = $caster->cast($type->getProperty('state'), $type->getProperty('state')->getType(), 'active', $request);
    expect($state)->toBeInstanceOf(MyActiveState::class);

    // Test empty value exception
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('state'), $type->getProperty('state')->getType(), '', $request);
    })->toThrow(CastException::class);
});

final class DummyValueObject
{
    public function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
final class DummyValueObjectFrom
{
    public function __construct(public string $value) {}

    public static function from(string $value): self
    {
        return new self($value);
    }
}
final class DummyValueObjectParse
{
    public function __construct(public string $value) {}

    public static function parse(string $value): self
    {
        return new self($value);
    }
}
final class DummyValueObjectTryFrom
{
    public function __construct(public string $value) {}

    public static function tryFrom(string $value): self
    {
        if ($value === 'error') {
            throw new Exception('error');
        }

        return new self($value);
    }
}

it('casts value object', function (): void {
    $caster = new ValueObjectCaster;
    $request = Request::create('/');
    $type = new ReflectionClass(new class
    {
        public DummyValueObject $vo;

        public DummyValueObjectFrom $voFrom;

        public DummyValueObjectParse $voParse;

        public DummyValueObjectTryFrom $voTryFrom;

        public int $builtin;

        public ?DummyValueObject $nullableVo;
    });

    expect($caster->supports($type->getProperty('builtin')->getType()))->toBeFalse();
    expect($caster->cast($type->getProperty('nullableVo'), $type->getProperty('nullableVo')->getType(), null, $request))->toBeNull();

    expect($caster->supports($type->getProperty('vo')->getType()))->toBeTrue();
    $vo = $caster->cast($type->getProperty('vo'), $type->getProperty('vo')->getType(), 'hello', $request);
    expect($vo)->toBeInstanceOf(DummyValueObject::class);
    expect($vo->value)->toBe('hello');

    $voFrom = $caster->cast($type->getProperty('voFrom'), $type->getProperty('voFrom')->getType(), 'hello', $request);
    expect($voFrom)->toBeInstanceOf(DummyValueObjectFrom::class);

    $voParse = $caster->cast($type->getProperty('voParse'), $type->getProperty('voParse')->getType(), 'hello', $request);
    expect($voParse)->toBeInstanceOf(DummyValueObjectParse::class);

    $voTryFrom = $caster->cast($type->getProperty('voTryFrom'), $type->getProperty('voTryFrom')->getType(), 'hello', $request);
    expect($voTryFrom)->toBeInstanceOf(DummyValueObjectTryFrom::class);

    // Exception on tryFrom error
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('voTryFrom'), $type->getProperty('voTryFrom')->getType(), 'error', $request);
    })->toThrow(CastException::class);

    // Exception on parse with empty string (falls through)
    expect(function () use ($caster, $type, $request): void {
        $caster->cast($type->getProperty('voParse'), $type->getProperty('voParse')->getType(), '', $request);
    })->toThrow(CastException::class);
});
