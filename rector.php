<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        /*
         * Skip selected rules
         */

        AddSeeTestAnnotationRector::class,

        /*
         * Skip selected rules in selected files
         */

        FirstClassCallableRector::class => [
            // Do not change callables in config
            __DIR__.'/config/*',
        ],
    ])
    ->withImportNames(importShortClasses: false)
    ->withPHPStanConfigs([
        __DIR__.'/vendor/phpstan/phpstan-phpunit/extension.neon',
        __DIR__.'/vendor/phpstan/phpstan-symfony/extension.neon',
        __DIR__.'/phpstan.neon',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        SymfonySetList::SYMFONY_64,
    ])
    ->withPreparedSets(codeQuality: true);
