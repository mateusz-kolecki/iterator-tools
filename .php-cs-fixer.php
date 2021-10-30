<?php

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new \PhpCsFixer\Config())
    ->setFinder($finder)
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
    ]);
