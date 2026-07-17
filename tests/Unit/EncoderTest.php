<?php

use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Encoding\ToonEncoder;
use Jayi\Toon\Enums\KeyFolding;
use Jayi\Toon\Toon;

it('encodes a simple object', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'id' => 123,
        'name' => 'Ada',
        'active' => true,
    ]);

    expect($result)->toBe("id: 123\nname: Ada\nactive: true");
});

it('encodes a nested object', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'user' => [
            'id' => 123,
            'name' => 'Ada',
        ],
    ]);

    expect($result)->toBe("user:\n  id: 123\n  name: Ada");
});

it('encodes a primitive array', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['tags' => ['admin', 'ops', 'dev']]);

    expect($result)->toBe('tags[3]: admin,ops,dev');
});

it('encodes an empty array as canonical form', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['tags' => []]);

    expect($result)->toBe('tags: []');
});

it('encodes a tabular array', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'items' => [
            ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
            ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
        ],
    ]);

    expect($result)->toBe("items[2]{sku,qty,price}:\n  A1,2,9.99\n  B2,1,14.5");
});

it('encodes a mixed array', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'items' => [1, 'text', true],
    ]);

    expect($result)->toBe('items[3]: 1,text,true');
});

it('encodes arrays of arrays', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'pairs' => [[1, 2], [3, 4]],
    ]);

    expect($result)->toBe("pairs[2]:\n  - [2]: 1,2\n  - [2]: 3,4");
});

it('encodes objects as list items', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'items' => [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second', 'extra' => true],
        ],
    ]);

    // Non-uniform objects (different key counts) should use list items
    expect($result)->toContain('items[2]:');
    expect($result)->toContain('- id: 1');
    expect($result)->toContain('name: First');
});

it('encodes empty root array as canonical form', function () {
    $encoder = new ToonEncoder;

    expect($encoder->encode([]))->toBe('[]');
});

it('encodes a single primitive at root', function () {
    $encoder = new ToonEncoder;

    expect($encoder->encode('hello'))->toBe('hello');
    expect($encoder->encode(42))->toBe('42');
    expect($encoder->encode(true))->toBe('true');
    expect($encoder->encode(null))->toBe('null');
});

it('quotes strings containing colons in tabular rows', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'links' => [
            ['id' => 1, 'url' => 'http://a:b'],
            ['id' => 2, 'url' => 'https://example.com?q=a:b'],
        ],
    ]);

    expect($result)->toContain('"http://a:b"');
    expect($result)->toContain('"https://example.com?q=a:b"');
});

it('encodes unicode and emoji', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'message' => 'Hello 世界 👋',
        'tags' => ['🎉', '🎊', '🎈'],
    ]);

    expect($result)->toContain('message: Hello 世界 👋');
    expect($result)->toContain('tags[3]: 🎉,🎊,🎈');
});

it('normalizes NaN and Infinity to null', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['a' => NAN, 'b' => INF, 'c' => -INF]);

    expect($result)->toBe("a: null\nb: null\nc: null");
});

it('normalizes DateTime to ISO string', function () {
    $encoder = new ToonEncoder;

    $date = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
    $result = $encoder->encode(['created' => $date]);

    // ISO strings contain colons, so they get quoted per §7.2
    expect($result)->toBe('created: "2025-01-01T00:00:00+00:00"');
});

it('encodes with key folding', function () {
    $encoder = new ToonEncoder(new EncoderOptions(keyFolding: KeyFolding::Safe));

    $result = $encoder->encode([
        'a' => ['b' => ['c' => 1]],
    ]);

    expect($result)->toBe('a.b.c: 1');
});

it('encodes with key folding and inline array', function () {
    $encoder = new ToonEncoder(new EncoderOptions(keyFolding: KeyFolding::Safe));

    $result = $encoder->encode([
        'data' => ['meta' => ['items' => ['x', 'y']]],
    ]);

    expect($result)->toBe('data.meta.items[2]: x,y');
});

it('does not produce trailing spaces or trailing newline', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
    ]);

    expect($result)->not->toEndWith("\n");

    $lines = explode("\n", $result);

    foreach ($lines as $line) {
        expect($line)->not->toMatch('/\s+$/');
    }
});

it('encodes quoted keys in headers', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'my-key' => [1, 2, 3],
    ]);

    expect($result)->toBe('"my-key"[3]: 1,2,3');
});

it('encodes nested tabular inside list item', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'items' => [
            [
                'users' => [
                    ['id' => 1, 'name' => 'Ada'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                'status' => 'active',
            ],
        ],
    ]);

    expect($result)->toContain('- users[2]{id,name}:');
    expect($result)->toContain('status: active');
});

it('estimates token savings', function () {
    $data = [
        'users' => [
            ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
        ],
    ];

    $result = Toon::savings($data);

    expect($result)->toHaveKeys(['json_chars', 'toon_chars', 'saved_chars', 'saved_percent']);
    expect($result['saved_chars'])->toBeGreaterThan(0);
    expect($result['saved_percent'])->toBeGreaterThan(0);
    expect($result['toon_chars'])->toBeLessThan($result['json_chars']);
});

it('produces smaller output with compact preset', function () {
    $data = [
        'a' => ['b' => ['c' => ['items' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]]]],
    ];

    $default = Toon::encode($data);
    $compact = Toon::compact($data);

    expect(strlen($compact))->toBeLessThan(strlen($default));
    expect($compact)->toContain('a.b.c.items');
});

it('compact uses indent of 1 and key folding', function () {
    $data = ['parent' => ['child' => 'value']];

    $compact = Toon::compact($data);

    // Key folding collapses single-key chains
    expect($compact)->toBe('parent.child: value');
});

it('smart picks compact for deeply nested data', function () {
    $data = ['a' => ['b' => ['c' => ['d' => 1]]]];

    $result = Toon::smart($data);

    expect($result)->toBe('a.b.c.d: 1');
});

it('smart picks default for flat data', function () {
    $data = ['id' => 1, 'name' => 'Ada'];

    $smart = Toon::smart($data);
    $default = Toon::encode($data);

    expect($smart)->toBe($default);
});

it('escapes control characters with \\uXXXX', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['msg' => "hello\x01world\x1f"]);

    expect($result)->toBe('msg: "hello\\u0001world\\u001f"');
});

it('does not use tabular form for arrays containing empty objects', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['items' => [['id' => 1], []]]);

    expect($result)->not->toContain('{');
    expect($result)->toContain('items[2]:');
});

it('encodes small floats in exponent form outside canonical range', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['v' => 1e-7]);

    expect($result)->toContain('e');
});

it('encodes large floats in exponent form outside canonical range', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['v' => 1e21]);

    expect($result)->toContain('e');
});

it('preserves object key order', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode([
        'z' => 1,
        'a' => 2,
        'm' => 3,
    ]);

    expect($result)->toBe("z: 1\na: 2\nm: 3");
});

it('skips folding when the folded key collides with a literal input key', function () {
    $encoder = new ToonEncoder(new EncoderOptions(keyFolding: KeyFolding::Safe));

    $result = $encoder->encode(['a' => ['b' => 1], 'a.b' => 2]);

    expect($result)->toBe("a:\n  b: 1\n\"a.b\": 2");
});

it('skips folding when the literal key comes before the foldable chain', function () {
    $encoder = new ToonEncoder(new EncoderOptions(keyFolding: KeyFolding::Safe));

    $result = $encoder->encode(['a.b' => 2, 'a' => ['b' => 1]]);

    expect($result)->toBe("\"a.b\": 2\na:\n  b: 1");
});

it('encodes empty inner arrays without a trailing space', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['x' => [[]]]);

    expect($result)->toBe("x[1]:\n  - [0]:");
});

it('encodes empty and non-empty inner arrays together', function () {
    $encoder = new ToonEncoder;

    $result = $encoder->encode(['x' => [[], [1]]]);

    expect($result)->toBe("x[2]:\n  - [0]:\n  - [1]: 1");
});

it('quotes literal dotted keys when key folding is enabled', function () {
    $encoder = new ToonEncoder(new EncoderOptions(keyFolding: KeyFolding::Safe));

    $result = $encoder->encode(['a.b' => 1]);

    expect($result)->toBe('"a.b": 1');
});
