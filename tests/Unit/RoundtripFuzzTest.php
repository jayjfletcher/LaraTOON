<?php

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Encoding\ToonEncoder;

/**
 * Deterministic property-style roundtrip suite: every structure must survive
 * decode(encode($x)) === $x with the strict decoder and default options.
 */
it('roundtrips gnarly structures losslessly in strict mode', function (mixed $data) {
    $encoder = new ToonEncoder;
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    expect($decoder->decode($encoder->encode($data)))->toBe($data);
})->with([
    'flat object' => [['id' => 1, 'name' => 'Ada', 'ok' => true, 'nil' => null]],
    'empty array' => [[]],
    'empty array value' => [['x' => []]],
    'list of empty arrays' => [[[], [], []]],
    'empty inner array beside values' => [['x' => [[], [1, 2], []]]],
    'scalar string root' => ['hello world'],
    'scalar with colon root' => ['note: hello'],
    'scalar int root' => [42],
    'scalar float root' => [3.14],
    'scalar bool root' => [true],
    'primitive list root' => [[1, 'two', false, null]],
    'nested lists in objects in lists' => [[
        'rows' => [
            ['cells' => [['v' => 1], ['v' => 2]], 'tag' => 'a'],
            ['cells' => [['v' => 3]], 'tag' => 'b'],
        ],
    ]],
    'list item with nested object first key' => [['list' => [['k' => ['sub' => 1], 'x' => 2]]]],
    'list item with nested list first key' => [['list' => [['k' => [['a' => 1], 'b'], 'x' => 2]]]],
    'deep single-key chains' => [['a' => ['b' => ['c' => ['d' => ['e' => 1]]]]]],
    'literal dotted key' => [['a.b' => 1]],
    'dotted key beside real nesting' => [['a' => ['b' => 1], 'a.b' => 2]],
    'dotted keys with multiple segments' => [['x.y.z' => 'deep', 'x' => ['y' => ['z' => 'nested']]]],
    'dotted tabular field names' => [['t' => [['a.b' => 1, 'c' => 2], ['a.b' => 3, 'c' => 4]]]],
    'unicode strings' => [['msg' => 'Hello 世界 👋', 'emoji' => ['🎉', '🎊']]],
    'unicode keys' => [['clé' => 1, '键' => 2]],
    'strings resembling numbers' => [['a' => '42', 'b' => '3.14', 'c' => '-0', 'd' => '1e5', 'e' => '007']],
    'strings resembling booleans and null' => [['a' => 'true', 'b' => 'false', 'c' => 'null']],
    'strings resembling toon syntax' => [['a' => '[3]: x,y,z', 'b' => '- item', 'c' => 'k[2]{a,b}:', 'd' => 'key: value']],
    'strings with quotes and escapes' => [['a' => 'say "hi"', 'b' => "line1\nline2", 'c' => "tab\there", 'd' => 'back\\slash']],
    'strings with delimiters' => [['a' => 'x,y', 'b' => 'p|q', 'c' => "t\tu"]],
    'whitespace edge strings' => [['a' => ' leading', 'b' => 'trailing ', 'c' => '', 'd' => '-']],
    'high precision floats' => [['a' => 1.0000000000000002, 'b' => 0.30000000000000004, 'c' => -0.1]],
    'extreme floats' => [['a' => 1.7976931348623157e308, 'b' => 2.2250738585072014e-308, 'c' => 1e-7]],
    'big integers' => [['max' => PHP_INT_MAX, 'min' => PHP_INT_MIN]],
    'tabular with quoted fields' => [['t' => [['my-key' => 1, 'b c' => 'x'], ['my-key' => 2, 'b c' => 'y']]]],
    'mixed list of scalars and objects' => [['items' => [1, ['a' => 2], 'three', [4, 5]]]],
    'lists of lists' => [['pairs' => [[1, 2], [3, 4], []]]],
    'object with single empty-string key' => [['' => 'value']],
    'keys needing quoting' => [['my-key' => 1, 'has space' => 2, 'colon:key' => 3]],
]);
