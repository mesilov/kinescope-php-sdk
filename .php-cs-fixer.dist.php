<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->path(['src/', 'tests/'])
    ->exclude([
        'vendor',
        'var',
        'coverage',
        '.phpunit.cache',
        '.phpstan.cache',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,

        // Import rules
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // Strict rules
        'strict_param' => true,
        'declare_strict_types' => true,

        // Array rules
        'array_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => true,

        // Blank line rules
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'switch', 'foreach', 'for', 'while'],
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
            ],
        ],

        // Class rules
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'final_class' => false,
        'no_blank_lines_after_class_opening' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        // Operator rules
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_successor_space' => true,

        // PHPDoc rules
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,

        // Semicolon rules
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction' => true,

        // Spacing rules
        'single_quote' => true,
        'cast_spaces' => ['space' => 'single'],
        'type_declaration_spaces' => true,

        // Control structure rules
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,

        // Function rules
        'function_typehint_space' => true,
        'lambda_not_used_import' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'return_type_declaration' => ['space_before' => 'none'],

        // Visibility rules
        'visibility_required' => [
            'elements' => ['const', 'method', 'property'],
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache');
