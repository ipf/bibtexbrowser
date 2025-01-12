<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude('node_modules')
    ->exclude('vendor')
    ->in(__DIR__);
$config = new PhpCsFixer\Config();
$config
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
return $config;
