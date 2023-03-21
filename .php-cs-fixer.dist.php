<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    '@PER' => true,
    '@Symfony' => true,

    'declare_strict_types' => true,

    // Override @Symfony

    'phpdoc_align' => [
        'align' => 'left',
        'tags' => [
            'method',
            'param',
            'property',
            'property-read',
            'property-write',
            'return',
            'throws',
            'type',
            'var',
        ],
    ],

    'phpdoc_separation' => [
        'groups' => [
            // Defaults
            ['deprecated', 'link', 'see', 'since'],
            ['author', 'copyright', 'license'],
            ['category', 'package', 'subpackage'],
            ['property', 'property-read', 'property-write'],

            // Overrides
            ['template', 'template-covariant', 'template-uses'],

            ['uses'],

            ['phpstan-*'], // This is not available... yet.

            ['phpstan-param', 'phpstan-return'],
        ],
    ],

    'global_namespace_import' => [
        'import_classes' => null,
        'import_constants' => true,
        'import_functions' => true,
    ],
    'no_empty_comment' => false,
    'php_unit_method_casing' => ['case' => 'snake_case'],
    'single_line_throw' => false,
    'yoda_style' => false,

    'phpdoc_to_comment' => [
        'ignored_tags' => [
            'array',
            'var',
            'phpstan-var',
        ],
    ],
];

$finder = Finder::create()
    ->in([
        __DIR__.'/src',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
