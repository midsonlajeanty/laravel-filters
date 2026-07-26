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
