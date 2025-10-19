<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'public/bundles'])
    ->notPath(['config/bundles.php', 'config/reference.php']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => false,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
        'ordered_imports' => true,
        'phpdoc_to_comment' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder);
