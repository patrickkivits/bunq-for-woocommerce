<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'src/Model/Generated'
    ]);

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR2'                               => true,
        'array_syntax'                        => [
            'syntax' => 'short'
        ],
        'class_keyword_remove'                => false,
        'concat_space'                        => [
            'spacing' => 'one'
        ],
        'combine_consecutive_unsets'          => true,
        'general_phpdoc_annotation_remove'    => [
            'annotations' => ['@author'],
        ],
        'linebreak_after_opening_tag'         => true,
        'echo_tag_syntax'                     => [
            'format' => 'long'
        ],
        'no_useless_return'                   => true,
        'not_operator_with_space'             => false,
        'not_operator_with_successor_space'   => false,
        'ordered_imports'                     => true,
        'phpdoc_add_missing_param_annotation' => true,
        'protected_to_private'                => true,
        'semicolon_after_instruction'         => true,
    ])
    ->setFinder($finder);