<?php

return [
    'indent_size' => 2,
    'delimiter' => 'comma',
    'key_folding' => 'off',
    'flatten_depth' => INF,
    'strict' => true,
    'expand_paths' => 'off',

    /*
    |--------------------------------------------------------------------------
    | PAO Test Output Override
    |--------------------------------------------------------------------------
    |
    | When enabled, test output from PAO (nunomaduro/pao) will be encoded as
    | TOON instead of JSON. Only takes effect when PAO is installed.
    |
    | Set the TOON_PAO_OUTPUT environment variable to "true" to enable.
    |
    */
    'pao_output' => env('TOON_PAO_OUTPUT', false),
];
