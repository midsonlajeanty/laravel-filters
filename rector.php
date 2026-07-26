<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
        privatization: true,
    )
    ->withSkip([
        __DIR__.'/src/QueryFilter.php',
        __DIR__.'/src/Exceptions/CastException.php',
    ])
    ->withPhpSets();
