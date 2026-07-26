<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://banners.beyondco.de/Laravel%20Filters.png?theme=dark&packageManager=composer+require&packageName=midsonlajeanty%2Flaravel-filters&pattern=architect&style=style_2&description=Strict%2C+attribute-driven+request+filtering+for+Laravel.&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg&widths=auto&heights=auto">
        <img src="https://banners.beyondco.de/Laravel%20Filters.png?theme=light&packageManager=composer+require&packageName=midsonlajeanty%2Flaravel-filters&pattern=architect&style=style_2&description=Strict%2C+attribute-driven+request+filtering+for+Laravel.&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg&widths=auto&heights=auto" width="100%" alt="Laravel Filters - Strict, attribute-driven model filtering for Laravel">
    </picture>
</p>

<p align="center">
    <a href="https://github.com/midsonlajeanty/laravel-filters/actions">
        <img src="https://github.com/midsonlajeanty/laravel-filters/actions/workflows/tests.yml/badge.svg" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/midsonlajeanty/laravel-filters">
        <img src="https://img.shields.io/packagist/dt/midsonlajeanty/laravel-filters" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/midsonlajeanty/laravel-filters">
        <img src="https://img.shields.io/packagist/v/midsonlajeanty/laravel-filters" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/midsonlajeanty/laravel-filters">
        <img src="https://img.shields.io/packagist/l/midsonlajeanty/laravel-filters" alt="License">
    </a>
</p>

Strict, attribute-driven model filtering for Laravel.

## Features

- **No repetitive `if` scopes.** Filter properties map directly to database columns. Attributes like `#[MapTo]` handle SQL operators automatically.
- **Strict typing.** Filters are strictly typed DTOs. PHP types are automatically casted from the request, including `Carbon`, `Enum`, `Uuid`, `Spatie\ModelStates`, and custom Value Objects.
- **Array expansion.** Comma-separated query strings (`?tags=php,laravel`) are automatically exploded into array properties. Configurable per-property via `#[Delimiter]`.
- **Decoupled architecture.** Hydration and filtering flow through a pipeline of extensible Pipes. Custom operators and casters are easily injected via the registry.
- **Fail-safe validation.** Missing `#[Required]` parameters or invalid casts instantly throw a standard Laravel `ValidationException` with field-level errors.

## Getting started

Requires PHP 8.3+ and Laravel 11.0+.

Install the package in your Laravel project:

```bash
composer require midsonlajeanty/laravel-filters
```

Optionally, publish the configuration to customize global delimiters, invalid value actions, casters, and operators:

```bash
php artisan vendor:publish --tag="filters-config"
```

## Usage

### Preparing the Model

Add the `Filterable` trait to any Eloquent model you want to filter:

```php
use Illuminate\Database\Eloquent\Model;
use Mds\LaravelFilters\Traits\Filterable;

class User extends Model
{
    use Filterable;
}
```

### Declaring a filter

Generate a new filter class:

```bash
php artisan make:filter UserFilter
```

Declare public properties representing your expected query string inputs. By default, properties map to columns of the same name with an `=` operator. PHP types are strictly enforced and automatically casted.

```php
namespace App\Filters;

use App\Enums\UserStatus;
use Carbon\Carbon;
use Mds\LaravelFilters\Attributes\MapTo;
use Mds\LaravelFilters\Attributes\Required;
use Mds\LaravelFilters\Attributes\Sorts;
use Mds\LaravelFilters\QueryFilter;

final class UserFilter extends QueryFilter
{
    public function __construct(
        #[Required]
        public readonly UserStatus $status,

        #[MapTo('email', operator: 'contains')]
        public readonly ?string $search = null,
        
        #[MapTo('created_at', operator: '>=')]
        public readonly ?Carbon $from_date = null,

        #[Sorts(default: ['created_at' => 'desc'])]
        public readonly array $sort = [],
    ) {}
}
```

### Custom filtering methods

If a property requires complex logic instead of a simple column mapping, define a method matching the property name. It receives the `Builder` and the casted value:

```php
final class ActiveFilter extends QueryFilter
{
    public function __construct(
        public readonly bool $active,
    ) {}

    public function active($query, bool $value): void
    {
        $query->whereNotNull('activated_at');
    }
}
```

### Applying the filter

Type-hint the Filter in your Controller. It is hydrated directly from the Request via Laravel's container:

```php
use App\Filters\UserFilter;

class UserController extends Controller
{
    public function index(UserFilter $filter)
    {
        return User::filter($filter)->paginate();
    }
}
```

## Contributing

You have a lot of options to contribute to this project! You can:

- [Fork](https://github.com/midsonlajeanty/laravel-filters) it on GitHub
- [Submit](https://github.com/midsonlajeanty/laravel-filters/issues) a bug report
- [Donate](https://paypal.me/midsonlajeanty) to the developer

## License

MIT
