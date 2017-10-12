<?php

return [
    'directory_list' => [
        'application',
    ],
    'exclude_analysis_directory_list' => [
        'application/vendor',
    ],
    'backward_compatibility_checks' => true,
    'quick_mode' => false,
    'analyze_signature_compatibility' => true,
    'minimum_severity' => 0,
    'allow_missing_properties' => false,
    'null_casts_as_any_type' => false,
    'null_casts_as_array' => false,
    'array_casts_as_null' => false,
    'scalar_implicit_cast' => false,
    'scalar_implicit_partial' => [],
    'ignore_undeclared_variables_in_global_scope' => false,
    'suppress_issue_types' => [
    ],
    'whitelist_issue_types' => [],
];
