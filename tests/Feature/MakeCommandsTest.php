<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->dirs = [
        app_path('Filters'),
        app_path('Casters'),
        app_path('Operators'),
    ];

    foreach ($this->dirs as $dir) {
        if (File::exists($dir)) {
            File::deleteDirectory($dir);
        }
    }
});

it('creates a filter', function (): void {
    Artisan::call('make:filter', ['name' => 'User']);
    expect(File::exists(app_path('Filters/UserFilter.php')))->toBeTrue();
});

it('creates a caster', function (): void {
    Artisan::call('make:caster', ['name' => 'Custom']);
    expect(File::exists(app_path('Casters/CustomCaster.php')))->toBeTrue();
});

it('creates an operator', function (): void {
    Artisan::call('make:operator', ['name' => 'Custom']);
    expect(File::exists(app_path('Operators/CustomOperator.php')))->toBeTrue();
});
