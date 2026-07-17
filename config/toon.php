<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Encoder Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults used by Toon::encode() when no EncoderOptions are passed.
    | Explicit options given by the caller always take precedence.
    |
    | delimiter: "comma", "tab", or "pipe".
    | key_folding: "off" or "safe".
    | flatten_depth: maximum folded segments, INF for unlimited.
    |
    */
    'indent_size' => 2,
    'delimiter' => 'comma',
    'key_folding' => 'off',
    'flatten_depth' => INF,

    /*
    |--------------------------------------------------------------------------
    | Decoder Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults used by Toon::decode() when no DecoderOptions are passed.
    | Explicit options given by the caller always take precedence.
    |
    | expand_paths: "off", "safe", or "auto".
    |
    */
    'strict' => true,
    'expand_paths' => 'auto',

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
