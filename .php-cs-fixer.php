<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,

        'array_syntax' => [
            'syntax' => 'short'
        ],

        'declare_strict_types' => true,

        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true
        ],

        // https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/function_notation/no_unreachable_default_argument_value.rst
        'no_unreachable_default_argument_value' => true,

        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        'no_unused_imports' => true,
        'lambda_not_used_import' => true,

        'yoda_style' => [
            'equal' => true,
            'identical' => true,
            'less_and_greater' => true
        ],

        'trim_array_spaces' => true,
    ]);
