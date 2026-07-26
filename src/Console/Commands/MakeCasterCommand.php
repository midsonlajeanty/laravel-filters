<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeCasterCommand extends GeneratorCommand
{
    protected $name = 'make:caster';

    protected $description = 'Create a new query filter caster class';

    protected $type = 'Caster';

    protected function getStub(): string
    {
        return __DIR__.'/../../../stubs/caster.stub';
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Casters';
    }

    #[\Override]
    protected function getNameInput(): string
    {
        /** @var string $name */
        $name = $this->argument('name');
        $name = trim($name);

        if (! str_ends_with($name, 'Caster')) {
            $name .= 'Caster';
        }

        return $name;
    }
}
