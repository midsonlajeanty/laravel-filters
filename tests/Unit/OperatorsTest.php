<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Mds\LaravelFilters\Operators\BetweenOperator;
use Mds\LaravelFilters\Operators\ContainsOperator;
use Mds\LaravelFilters\Operators\EndsOperator;
use Mds\LaravelFilters\Operators\EqualsOperator;
use Mds\LaravelFilters\Operators\GreaterThanOperator;
use Mds\LaravelFilters\Operators\GreaterThanOrEqualOperator;
use Mds\LaravelFilters\Operators\InOperator;
use Mds\LaravelFilters\Operators\LessThanOperator;
use Mds\LaravelFilters\Operators\LessThanOrEqualOperator;
use Mds\LaravelFilters\Operators\StartsOperator;

final class DummyUserModel extends Model
{
    protected $table = 'users';
}

beforeEach(function (): void {
    $this->query = DummyUserModel::query();
});

it('applies BetweenOperator', function (): void {
    $operator = new BetweenOperator;
    $operator->apply($this->query, 'age', [10, 20]);
    expect($this->query->toSql())->toContain('between ? and ?');
});

it('applies ContainsOperator', function (): void {
    $operator = new ContainsOperator;
    $operator->apply($this->query, 'name', 'john');
    expect($this->query->toSql())->toContain('LIKE ?');
});

it('applies EndsOperator', function (): void {
    $operator = new EndsOperator;
    $operator->apply($this->query, 'name', 'john');
    expect($this->query->toSql())->toContain('LIKE ?');
});

it('applies EqualsOperator', function (): void {
    $operator = new EqualsOperator;
    $operator->apply($this->query, 'name', 'john');
    expect($this->query->toSql())->toContain('= ?');
});

it('applies GreaterThanOperator', function (): void {
    $operator = new GreaterThanOperator;
    $operator->apply($this->query, 'age', 18);
    expect($this->query->toSql())->toContain('> ?');
});

it('applies GreaterThanOrEqualOperator', function (): void {
    $operator = new GreaterThanOrEqualOperator;
    $operator->apply($this->query, 'age', 18);
    expect($this->query->toSql())->toContain('>= ?');
});

it('applies InOperator', function (): void {
    $operator = new InOperator;
    $operator->apply($this->query, 'id', [1, 2]);
    expect($this->query->toSql())->toContain('in (?, ?)');
});

it('applies LessThanOperator', function (): void {
    $operator = new LessThanOperator;
    $operator->apply($this->query, 'age', 18);
    expect($this->query->toSql())->toContain('< ?');
});

it('applies LessThanOrEqualOperator', function (): void {
    $operator = new LessThanOrEqualOperator;
    $operator->apply($this->query, 'age', 18);
    expect($this->query->toSql())->toContain('<= ?');
});

it('applies StartsOperator', function (): void {
    $operator = new StartsOperator;
    $operator->apply($this->query, 'name', 'john');
    expect($this->query->toSql())->toContain('LIKE ?');
});
