<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0.0-rc.1|configurator
 * you can change this configuration by importing this file.
 */

$config = new PhpCsFixer\Config();

return $config->setRiskyAllowed(true)
    ->setRules([
        // Rule sets
        '@PSR1'        => true,
        '@PSR2'        => true,
        '@PSR12:risky' => true,
        '@PSR12'       => true,

        '@PHP54Migration'       => true,
        '@PHP56Migration:risky' => true,
        '@PHP70Migration:risky' => true,
        '@PHP70Migration'       => true,
        '@PHP71Migration:risky' => true,
        '@PHP71Migration'       => true,
        '@PHP73Migration'       => true,
        '@PHP74Migration:risky' => true,
        '@PHP74Migration'       => true,
        '@PHP80Migration:risky' => true,
        '@PHP80Migration'       => true,
        '@PHP81Migration'       => true,
        '@PHP82Migration'       => true,

        '@PHPUnit30Migration:risky' => true,
        '@PHPUnit32Migration:risky' => true,
        '@PHPUnit35Migration:risky' => true,
        '@PHPUnit43Migration:risky' => true,
        '@PHPUnit48Migration:risky' => true,
        '@PHPUnit50Migration:risky' => true,
        '@PHPUnit52Migration:risky' => true,
        '@PHPUnit54Migration:risky' => true,
        '@PHPUnit55Migration:risky' => true,
        '@PHPUnit56Migration:risky' => true,
        '@PHPUnit57Migration:risky' => true,
        '@PHPUnit60Migration:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        '@PHPUnit84Migration:risky' => true,

        // Exceptions to rulesets
        'declare_strict_types' => false,
        'use_arrow_functions'  => false,

        // Custom rules
        'array_indentation'      => true,
        'array_push'             => true,
        'backtick_to_shell_exec' => true,
        'binary_operator_spaces' => [
            'operators' => [
                '='  => 'single_space',
                '=>' => 'align',
            ],
        ],
        'blank_line_before_statement'                      => ['statements' => ['continue', 'declare', 'return', 'throw', 'try']],
        'cast_spaces'                                      => ['space'=>'none'],
        'class_attributes_separation'                      => ['elements' => ['method' => 'one']],
        'clean_namespace'                                  => true,
        'combine_consecutive_issets'                       => true,
        'combine_consecutive_unsets'                       => true,
        'concat_space'                                     => ['spacing'=>'one'],
        'dir_constant'                                     => true,
        'fopen_flag_order'                                 => true,
        'fopen_flags'                                      => true,
        'fully_qualified_strict_types'                     => true,
        'function_to_constant'                             => true,
        'function_typehint_space'                          => true,
        'global_namespace_import'                          => true,
        'heredoc_to_nowdoc'                                => true,
        'include'                                          => true,
        'is_null'                                          => true,
        'mb_str_functions'                                 => true,
        'method_chaining_indentation'                      => true,
        'modernize_types_casting'                          => false,
        'multiline_comment_opening_closing'                => true,
        'no_alias_language_construct_call'                 => true,
        'no_alternative_syntax'                            => true,
        'no_blank_lines_after_phpdoc'                      => true,
        'no_empty_comment'                                 => true,
        'no_empty_phpdoc'                                  => true,
        'no_empty_statement'                               => true,
        'no_extra_blank_lines'                             => true,
        'no_leading_namespace_whitespace'                  => true,
        'no_multiline_whitespace_around_double_arrow'      => true,
        'no_null_property_initialization'                  => true,
        'no_short_bool_cast'                               => true,
        'no_singleline_whitespace_before_semicolons'       => true,
        'no_spaces_around_offset'                          => true,
        'no_superfluous_elseif'                            => true,
        'no_superfluous_phpdoc_tags'                       => true,
        'no_trailing_comma_in_list_call'                   => true,
        'no_trailing_comma_in_singleline_array'            => true,
        'no_unneeded_control_parentheses'                  => true,
        'no_unneeded_curly_braces'                         => true,
        'no_unneeded_final_method'                         => true,
        'no_unset_cast'                                    => true,
        'no_unset_on_property'                             => true,
        'no_unused_imports'                                => true,
        'no_useless_else'                                  => true,
        'no_useless_return'                                => true,
        'no_useless_sprintf'                               => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'object_operator_without_whitespace'               => true,
        'operator_linebreak'                               => true,
        'ordered_imports'                                  => ['sort_algorithm' => 'alpha'],
        'ordered_interfaces'                               => true,
        'ordered_traits'                                   => true,
        'php_unit_construct'                               => true,
        'php_unit_set_up_tear_down_visibility'             => true,
        'php_unit_strict'                                  => true,
        'php_unit_test_annotation'                         => true,
        'php_unit_test_case_static_method_calls'           => true,
        'phpdoc_align'                                     => true,
        'phpdoc_annotation_without_dot'                    => true,
        'phpdoc_indent'                                    => true,
        'phpdoc_line_span'                                 => ['const'=>'single', 'property' => 'single'],
        'phpdoc_no_alias_tag'                              => true,
        'phpdoc_order'                                     => true,
        'phpdoc_order_by_value'                            => true,
        'phpdoc_scalar'                                    => true,
        'phpdoc_separation'                                => true,
        'phpdoc_single_line_var_spacing'                   => true,
        'phpdoc_summary'                                   => true,
        'phpdoc_tag_casing'                                => true,
        'phpdoc_to_comment'                                => true,
        'phpdoc_trim'                                      => true,
        'phpdoc_trim_consecutive_blank_line_separation'    => true,
        'phpdoc_types_order'                               => true,
        'phpdoc_var_without_name'                          => true,
        'regular_callable_call'                            => true,
        'return_assignment'                                => true,
        'self_accessor'                                    => true,
        'self_static_accessor'                             => true,
        'semicolon_after_instruction'                      => true,
        'set_type_to_cast'                                 => true,
        'simple_to_complex_string_variable'                => true,
        'simplified_if_return'                             => true,
        'single_line_comment_style'                        => true,
        'single_quote'                                     => true,
        'single_space_after_construct'                     => true,
        'standardize_increment'                            => true,
        'standardize_not_equals'                           => true,
        'strict_comparison'                                => true,
        'strict_param'                                     => true,
        'switch_continue_to_break'                         => true,
        'ternary_to_elvis_operator'                        => true,
        'trim_array_spaces'                                => true,
        'unary_operator_spaces'                            => true,
        'whitespace_after_comma_in_array'                  => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    )
;
