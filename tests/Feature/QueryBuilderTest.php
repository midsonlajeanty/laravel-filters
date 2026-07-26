<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Mds\LaravelFilters\Attributes\MapTo;
use Mds\LaravelFilters\Attributes\Sorts;
use Mds\LaravelFilters\QueryFilter;

class TestModel extends Model {
    protected $table = 'test';
}

class FullFilter extends QueryFilter {
    public string $name;
    
    #[MapTo('db_column')]
    public string $mapped;
    
    #[Sorts]
    public array $sorts;
    
    public array $price;
    
    public array $list;
    public \Illuminate\Support\Collection $collection;
    public \Carbon\Carbon $carbon;
    
    #[\Mds\LaravelFilters\Attributes\Hidden]
    public string $secret;
    
    public string $custom;
    public function filterCustom($query, $value): void {
        $query->where('custom', 'LIKE', $value);
    }
}

beforeEach(function (): void {
    $this->query = TestModel::query();
});

it('applies operators correctly to query builder', function (): void {
    $filter = new FullFilter();
    $filter->price = ['gt' => 10, 'lt' => 20];
    
    $filter->apply($this->query);
    
    $sql = $this->query->toSql();
    expect($sql)->toContain('> ?');
    expect($sql)->toContain('< ?');
});

it('applies sorts', function (): void {
    $filter = new FullFilter();
    $filter->sorts = ['name', '-age'];
    
    $filter->apply($this->query);
    
    $sql = $this->query->toSql();
    expect($sql)->toContain('order by "name" asc, "age" desc');
});

it('applies map to', function (): void {
    $filter = new FullFilter();
    $filter->mapped = 'test';
    
    $filter->apply($this->query);
    
    $sql = $this->query->toSql();
    expect($sql)->toContain('"db_column" = ?');
});

it('tests query filter types and custom methods', function (): void {
    $filter = new FullFilter();
    $filter->list = ['a', 'b'];
    $filter->collection = collect(['c']);
    $filter->carbon = \Carbon\Carbon::now();
    $filter->custom = 'test';
    $filter->sorts = ['', null]; // test empty string sort
    
    $filter->apply($this->query);
    
    $sql = $this->query->toSql();
    expect($sql)->toContain('"list" in (?, ?)');
    expect($sql)->toContain('"collection" in (?)');
    expect($sql)->toContain('"carbon" = ?');
    expect($sql)->toContain('"custom" LIKE ?');
});



it('tests toArray', function (): void {
    $filter = new FullFilter();
    $filter->name = 'test';
    $filter->secret = 'hidden';
    
    $array = $filter->toArray();
    expect($array)->toHaveKey('name', 'test');
    expect($array)->not->toHaveKey('secret');
});

class FilterableModel extends Model {
    use \Mds\LaravelFilters\Traits\Filterable;
    protected $table = 'test';
}

it('applies scopeFilter', function (): void {
    $filter = new FullFilter();
    $filter->name = 'john';

    $query = FilterableModel::query()->filter($filter);
    expect($query->toSql())->toContain('"name" = ?');
});

class WhitelistedSortFilter extends QueryFilter {
    #[Sorts(allowed: ['name', 'email'])]
    public array $sorts;
}

it('rejects sort columns not in whitelist', function (): void {
    $filter = new WhitelistedSortFilter();
    $filter->sorts = ['name', '-password', 'email'];

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('order by "name" asc, "email" asc');
    expect($sql)->not->toContain('password');
});

class DefaultSortFilter extends QueryFilter {
    #[Sorts(
        allowed: ['created_at', 'name'],
        default: ['created_at' => 'desc'],
    )]
    public array $sorts;
}

it('applies default sorts when no sorts provided', function (): void {
    $filter = new DefaultSortFilter();
    $filter->sorts = [];

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('order by "created_at" desc');
});

class UnauthorizedDefaultSortFilter extends QueryFilter {
    #[Sorts(
        allowed: ['name'],
        default: ['created_at' => 'desc', 'name' => 'asc'],
    )]
    public array $sorts;
}

it('ignores default sort columns not in whitelist', function (): void {
    $filter = new UnauthorizedDefaultSortFilter();
    $filter->sorts = [];

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('order by "name" asc');
    expect($sql)->not->toContain('"created_at"');
});

it('does not apply defaults when user provides sorts', function (): void {
    $filter = new DefaultSortFilter();
    $filter->sorts = ['name'];

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('order by "name" asc');
    expect($sql)->not->toContain('"created_at"');
});

class MapToOperatorFilter extends QueryFilter {
    #[MapTo('email', operator: 'contains')]
    public string $search;

    #[MapTo('age', operator: 'gte')]
    public int $min_age;
}

it('applies MapTo with operator parameter', function (): void {
    $filter = new MapToOperatorFilter();
    $filter->search = 'john';

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('"email" LIKE ?');

    $bindings = $query->getBindings();
    expect($bindings[0])->toBe('%john%');
});

it('applies MapTo with gte operator', function (): void {
    $filter = new MapToOperatorFilter();
    $filter->min_age = 18;

    $query = TestModel::query();
    $filter->apply($query);

    $sql = $query->toSql();
    expect($sql)->toContain('"age" >= ?');
});

it('throws CastException for non-numeric int values', function (): void {
    $caster = new \Mds\LaravelFilters\Casters\BuiltinCaster();
    $request = \Illuminate\Http\Request::create('/');

    $type = new ReflectionClass(new class {
        public int $age;
    });

    expect(function () use ($caster, $type, $request): void {
        $caster->cast(
            $type->getProperty('age'),
            $type->getProperty('age')->getType(),
            'abc',
            $request
        );
    })->toThrow(\Mds\LaravelFilters\Exceptions\CastException::class);
});

it('CarbonCaster throws for empty non-nullable value', function (): void {
    $caster = new \Mds\LaravelFilters\Casters\CarbonCaster();
    $request = \Illuminate\Http\Request::create('/');

    $type = new ReflectionClass(new class {
        public \Carbon\Carbon $date;
    });

    expect(function () use ($caster, $type, $request): void {
        $caster->cast(
            $type->getProperty('date'),
            $type->getProperty('date')->getType(),
            '',
            $request
        );
    })->toThrow(\Mds\LaravelFilters\Exceptions\CastException::class);
});
