<?php

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Encoding\ToonEncoder;
use Jayi\Toon\Enums\KeyFolding;
use Jayi\Toon\Enums\PathExpansion;
use Jayi\Toon\Toon;

function roundtrip(mixed $data, ?EncoderOptions $encOpts = null, ?DecoderOptions $decOpts = null): mixed
{
    $encoder = new ToonEncoder($encOpts ?? new EncoderOptions);
    $decoder = new ToonDecoder($decOpts ?? new DecoderOptions);

    $encoded = $encoder->encode($data);

    return $decoder->decode($encoded);
}

it('roundtrips a simple object', function () {
    $data = ['id' => 123, 'name' => 'Ada', 'active' => true];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a nested object', function () {
    $data = [
        'user' => [
            'id' => 123,
            'name' => 'Ada',
        ],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a primitive array', function () {
    $data = ['tags' => ['admin', 'ops', 'dev']];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a tabular array', function () {
    $data = [
        'users' => [
            ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
        ],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips arrays of arrays', function () {
    $data = ['pairs' => [[1, 2], [3, 4]]];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips mixed types', function () {
    $data = [
        'string' => 'hello',
        'int' => 42,
        'float' => 3.14,
        'bool' => true,
        'null' => null,
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips unicode and emoji', function () {
    $data = [
        'message' => 'Hello 世界 👋',
        'tags' => ['🎉', '🎊', '🎈'],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips strings that need quoting', function () {
    $data = [
        'empty' => '',
        'reserved_true' => 'true',
        'reserved_false' => 'false',
        'reserved_null' => 'null',
        'numeric' => '42',
        'with_colon' => 'key: value',
        'with_comma' => 'a,b',
        'with_hyphen' => '-start',
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips deeply nested structures', function () {
    $data = [
        'root' => [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'items' => [
                            ['id' => 1, 'val' => 'a'],
                            ['id' => 2, 'val' => 'b'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips with key folding and path expansion', function () {
    $data = [
        'a' => [
            'b' => [
                'c' => 1,
            ],
        ],
    ];

    $encOpts = new EncoderOptions(keyFolding: KeyFolding::Safe);
    $decOpts = new DecoderOptions(expandPaths: PathExpansion::Safe);

    expect(roundtrip($data, $encOpts, $decOpts))->toBe($data);
});

it('roundtrips folded arrays with path expansion', function () {
    $data = [
        'data' => [
            'meta' => [
                'items' => ['x', 'y'],
            ],
        ],
    ];

    $encOpts = new EncoderOptions(keyFolding: KeyFolding::Safe);
    $decOpts = new DecoderOptions(expandPaths: PathExpansion::Safe);

    expect(roundtrip($data, $encOpts, $decOpts))->toBe($data);
});

it('roundtrips an empty object', function () {
    // Empty assoc array encodes as [0]: which decodes as empty array
    $encoder = new ToonEncoder;
    $encoded = $encoder->encode(['data' => []]);

    $decoder = new ToonDecoder;
    $decoded = $decoder->decode($encoded);

    expect($decoded)->toBe(['data' => []]);
});

it('roundtrips quoted keys', function () {
    $data = [
        'my-key' => [1, 2, 3],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a list item whose first key holds a nested object', function () {
    $data = ['list' => [['k' => ['sub' => 1], 'x' => 2]]];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a list item whose first key holds a nested list', function () {
    $data = ['list' => [['k' => [['a' => 1], 'scalar'], 'x' => 2]]];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a list item with multiple keys after a nested first value', function () {
    $data = [
        'list' => [
            ['k' => ['sub' => ['deep' => 1]], 'x' => 2, 'y' => 'three'],
            ['k' => ['sub' => ['deep' => 4]], 'x' => 5, 'y' => 'six'],
        ],
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips literal dotted keys without path expansion', function () {
    $data = ['a.b' => 1];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips literal dotted keys alongside real nesting', function () {
    $data = ['a' => ['b' => 1], 'a.b' => 2];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips CRLF line endings', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("a: 1\r\nb:\r\n  c: 2\r\n");

    expect($result)->toBe(['a' => 1, 'b' => ['c' => 2]]);
});

it('roundtrips an empty inner array in strict mode', function () {
    $data = ['x' => [[]]];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips high-precision floats', function () {
    $data = [
        'a' => 1.0000000000000002,
        'b' => 0.30000000000000004,
        'c' => -2.2250738585072014e-308,
    ];

    expect(roundtrip($data))->toBe($data);
});

it('roundtrips a root scalar containing a colon', function () {
    expect(roundtrip('note: hello'))->toBe('note: hello');
});

it('decodes an empty string to an empty array', function () {
    // A root-level empty object encodes to '' and decodes back as [] —
    // root empty objects are not distinguishable from empty arrays.
    expect(Toon::encode(new stdClass))->toBe('');
    expect(Toon::decode(''))->toBe([]);
});
