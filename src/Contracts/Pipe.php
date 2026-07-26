<?php

declare(strict_types=1);

namespace Mds\LaravelFilters\Contracts;

use Closure;
use Mds\LaravelFilters\Context\PropertyContext;

interface Pipe
{
    /**
     * Handle the given context and pass it to the next pipe in the chain.
     *
     * @param  PropertyContext  $context  The context of the property being processed.
     * @param  Closure  $next  The next pipe in the chain.
     * @return mixed The result of processing the context.
     */
    public function handle(PropertyContext $context, Closure $next): mixed;
}
