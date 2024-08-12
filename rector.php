<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        SymfonySetList::SYMFONY_61,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SetList::CODE_QUALITY,
    ])
    ->withTypeCoverageLevel(0);
