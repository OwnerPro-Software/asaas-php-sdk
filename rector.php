<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnArrayDocblockBasedOnArrayMapRector;
use RectorLaravel\Rector\MethodCall\ContainerBindConcreteWithClosureOnlyRector;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withPhpSets(php82: true)
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        ContainerBindConcreteWithClosureOnlyRector::class => [
            __DIR__.'/src/AsaasServiceProvider.php',
        ],
        AddReturnArrayDocblockBasedOnArrayMapRector::class => [
            __DIR__.'/src/Support/HasArrayFactory.php',
        ],
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
    );
