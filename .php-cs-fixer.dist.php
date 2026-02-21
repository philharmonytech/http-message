<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'single_quote' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'trim_array_spaces' => true,
        'native_function_casing' => true,
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
        ],
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setCacheFile('.php-cs-fixer.cache');
