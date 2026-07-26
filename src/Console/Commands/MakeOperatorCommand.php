<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Console\Commands;

use Illuminate\Console\GeneratorCommand;

final class MakeOperatorCommand extends GeneratorCommand
{
    protected $name = 'make:operator';

    protected $description = 'Create a new query filter operator class';

    protected $type = 'Operator';

    protected function getStub(): string
    {
        return __DIR__.'/../../../stubs/operator.stub';
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Operators';
    }

    #[\Override]
    protected function getNameInput(): string
    {
        /** @var string $name */
        $name = $this->argument('name');
        $name = trim($name);

        if (! str_ends_with($name, 'Operator')) {
            $name .= 'Operator';
        }

        return $name;
    }
}
