<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeFilterCommand extends GeneratorCommand
{
    protected $name = 'make:filter';

    protected $description = 'Create a new query filter class';

    protected $type = 'Filter';

    protected function getStub(): string
    {
        return __DIR__.'/../../../stubs/filter.stub';
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Filters';
    }

    #[\Override]
    protected function getNameInput(): string
    {
        /** @var string $name */
        $name = $this->argument('name');
        $name = trim($name);

        if (! str_ends_with($name, 'Filter')) {
            $name .= 'Filter';
        }

        return $name;
    }
}
