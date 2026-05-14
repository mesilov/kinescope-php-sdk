<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/tests/Fixtures',
        ArrayToFirstClassCallableRector::class => [
            __DIR__ . '/src/Infrastructure/Console/ContainerFactory.php',
        ],
        NewInInitializerRector::class => [
            __DIR__ . '/src/Core/ResponseHandler.php',
        ],
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon',
    ])
    ->withCache(
        cacheDirectory: __DIR__ . '/.rector.cache',
    );
