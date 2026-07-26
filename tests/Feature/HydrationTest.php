<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Mds\LaravelFilters\Attributes\QueryName;
use Mds\LaravelFilters\FilterHydrator;
use Mds\LaravelFilters\QueryFilter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

enum StatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

final class TestFilter extends QueryFilter
{
    public function __construct(
        public readonly string $search,
        public readonly int $page = 1,
        #[QueryName('status_filter')]
        public readonly ?StatusEnum $status = null,
        public readonly ?UuidInterface $uuid = null
    ) {}
}

class TestDTOFilter extends QueryFilter
{
    public string $name;

    public int $age;

    public ?string $optional = null;
    
    public readonly string $readOnlyProp;
}

it('hydrates a complex DTO from request', function (): void {
    $uuid = Uuid::uuid4()->toString();
    $request = Request::create('/test', 'GET', [
        'search' => 'john',
        'status_filter' => 'active',
        'uuid' => $uuid,
    ]);

    $hydrator = new FilterHydrator;
    $filter = $hydrator->hydrate(TestFilter::class, $request);

    expect($filter->search)->toBe('john')
        ->and($filter->page)->toBe(1)
        ->and($filter->status)->toBe(StatusEnum::ACTIVE)
        ->and($filter->uuid->toString())->toBe($uuid);
});

it('hydrates public properties', function (): void {
    $request = Request::create('/', 'GET', [
        'name' => 'John',
        'age' => '30',
        'readOnlyProp' => 'ShouldNotSet'
    ]);

    $hydrator = new FilterHydrator;
    $filter = $hydrator->hydrate(TestDTOFilter::class, $request);

    expect($filter->name)->toBe('John');
    expect($filter->age)->toBe(30);
    expect($filter->optional)->toBeNull();
    expect(isset($filter->readOnlyProp))->toBeFalse();
});
