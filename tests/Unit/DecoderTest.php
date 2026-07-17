<?php

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Enums\PathExpansion;
use Jayi\Toon\Exceptions\ToonDecodeException;
use Jayi\Toon\Exceptions\ToonStrictModeException;

it('decodes a simple object', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("id: 123\nname: Ada\nactive: true");

    expect($result)->toBe(['id' => 123, 'name' => 'Ada', 'active' => true]);
});

it('decodes a nested object', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("user:\n  id: 123\n  name: Ada");

    expect($result)->toBe(['user' => ['id' => 123, 'name' => 'Ada']]);
});

it('decodes a primitive array', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('tags[3]: admin,ops,dev');

    expect($result)->toBe(['tags' => ['admin', 'ops', 'dev']]);
});

it('decodes an empty array (legacy form)', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode('tags[0]:');

    expect($result)->toBe(['tags' => []]);
});

it('decodes an empty array (canonical form)', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('tags: []');

    expect($result)->toBe(['tags' => []]);
});

it('decodes a tabular array', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("items[2]{sku,qty,price}:\n  A1,2,9.99\n  B2,1,14.5");

    expect($result)->toBe([
        'items' => [
            ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
            ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
        ],
    ]);
});

it('decodes arrays of arrays', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("pairs[2]:\n  - [2]: 1,2\n  - [2]: 3,4");

    expect($result)->toBe(['pairs' => [[1, 2], [3, 4]]]);
});

it('decodes objects as list items', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("items[2]:\n  - id: 1\n    name: First\n  - id: 2\n    name: Second");

    expect($result)->toBe([
        'items' => [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ],
    ]);
});

it('decodes a single primitive at root', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    expect($decoder->decode('hello'))->toBe('hello');
    expect($decoder->decode('42'))->toBe(42);
    expect($decoder->decode('true'))->toBe(true);
    expect($decoder->decode('null'))->toBeNull();
});

it('decodes empty document as empty object', function () {
    // Spec §5: an empty document decodes to an empty object, not an empty array.
    $decoder = new ToonDecoder;

    expect($decoder->decode(''))->toEqual(new stdClass);
});

it('decodes quoted strings', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('name: "hello world"');

    expect($result)->toBe(['name' => 'hello world']);
});

it('decodes escape sequences', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('msg: "line1\\nline2"');

    expect($result)->toBe(['msg' => "line1\nline2"]);
});

it('decodes quoted keys', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('"my-key"[3]: 1,2,3');

    expect($result)->toBe(['my-key' => [1, 2, 3]]);
});

it('decodes nested tabular inside list item', function () {
    $decoder = new ToonDecoder;

    $input = "items[1]:\n  - users[2]{id,name}:\n      1,Ada\n      2,Bob\n    status: active";

    $result = $decoder->decode($input);

    expect($result)->toBe([
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
});

it('decodes root tabular array', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[2]{id,name}:\n  1,Alice\n  2,Bob");

    expect($result)->toBe([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ]);
});

it('decodes root primitive array', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('[3]: a,b,c');

    expect($result)->toBe(['a', 'b', 'c']);
});

it('decodes empty nested object', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("root:\n  level1:");

    expect($result)->toEqual(['root' => ['level1' => new stdClass]]);
});

it('preserves key order', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("z: 1\na: 2\nm: 3");

    expect(array_keys($result))->toBe(['z', 'a', 'm']);
});

it('decodes with path expansion', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Safe));

    $result = $decoder->decode("a.b.c: 1\na.b.d: 2\na.e: 3");

    expect($result)->toBe(['a' => ['b' => ['c' => 1, 'd' => 2], 'e' => 3]]);
});

it('decodes with path expansion on arrays', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Safe));

    $result = $decoder->decode('data.meta.items[2]: a,b');

    expect($result)->toBe(['data' => ['meta' => ['items' => ['a', 'b']]]]);
});

it('does not expand paths when off', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Off));

    $result = $decoder->decode('user.name: Ada');

    expect($result)->toBe(['user.name' => 'Ada']);
});

it('auto-expands dotted keys by default', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('user.name: Ada');

    expect($result)->toBe(['user' => ['name' => 'Ada']]);
});

it('auto skips expansion when no dotted keys', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("name: Ada\nrole: admin");

    expect($result)->toBe(['name' => 'Ada', 'role' => 'admin']);
});

it('auto-detects indent size', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("user:\n child: value");

    expect($result)->toBe(['user' => ['child' => 'value']]);
});

it('throws on expansion conflict in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Safe));

    $decoder->decode("a.b: 1\na: 2");
})->throws(ToonStrictModeException::class);

it('applies LWW on expansion conflict in non-strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false, expandPaths: PathExpansion::Safe));

    $result = $decoder->decode("a.b: 1\na: 2");

    expect($result)->toBe(['a' => 2]);
});

// Strict mode error tests

it('throws on count mismatch in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('tags[5]: a,b,c');
})->throws(ToonStrictModeException::class);

it('throws on tabular row count mismatch', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("items[3]{id,name}:\n  1,Alice\n  2,Bob");
})->throws(ToonStrictModeException::class);

it('throws on tabular row width mismatch', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("items[1]{id,name}:\n  1,Alice,extra");
})->throws(ToonStrictModeException::class);

it('throws on invalid escape sequence', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('name: "bad\\xescape"');
})->throws(ToonDecodeException::class);

it('throws on indentation errors in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(indentSize: 2));

    $decoder->decode("user:\n   name: Ada");
})->throws(ToonStrictModeException::class);

it('types unquoted primitives correctly', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("a: true\nb: false\nc: null\nd: 42\ne: 3.14\nf: hello");

    expect($result['a'])->toBeTrue();
    expect($result['b'])->toBeFalse();
    expect($result['c'])->toBeNull();
    expect($result['d'])->toBe(42);
    expect($result['e'])->toBe(3.14);
    expect($result['f'])->toBe('hello');
});

it('treats leading-zero tokens as strings', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("code: 05\nzip: 0001");

    expect($result['code'])->toBe('05');
    expect($result['zip'])->toBe('0001');
});

it('decodes \\uXXXX escape sequences', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('msg: "hello\\u0001world"');

    expect($result)->toBe(['msg' => "hello\x01world"]);
});

it('rejects lone surrogate \\uXXXX in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('msg: "\\ud800"');
})->throws(ToonDecodeException::class);

it('throws on duplicate keys in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("name: Alice\nname: Bob");
})->throws(ToonStrictModeException::class);

it('applies LWW for duplicate keys in non-strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode("name: Alice\nname: Bob");

    expect($result)->toBe(['name' => 'Bob']);
});

it('decodes empty list item objects', function () {
    // Spec §10: a bare dash is an empty-object list item.
    $decoder = new ToonDecoder;

    $result = $decoder->decode("items[2]:\n  -\n  -");

    expect($result)->toEqual(['items' => [new stdClass, new stdClass]]);
});

it('decodes surrogate pair escapes into astral codepoints', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('s: "😀"');

    expect($result)->toBe(['s' => '😀']);
});

it('throws on a lone high surrogate escape', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('s: "\uD800"');
})->throws(ToonDecodeException::class, 'lone surrogate');

it('throws on a lone low surrogate escape', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('s: "\uDC00"');
})->throws(ToonDecodeException::class, 'lone surrogate');

it('throws on a high surrogate followed by a non-surrogate escape', function () {
    $decoder = new ToonDecoder;

    $decoder->decode('s: "\uD83DA"');
})->throws(ToonDecodeException::class, 'lone surrogate');

it('decodes nested children after trailing whitespace on a key line in lenient mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode("parent: \n  child: 1");

    expect($result)->toBe(['parent' => ['child' => 1]]);
});

it('decodes a quoted root scalar containing a colon', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('"note: hello"');

    expect($result)->toBe('note: hello');
});

it('throws on unconsumed lines after the document root in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $caught = null;

    try {
        $decoder->decode("a: 1\n    b: 2");
    } catch (ToonStrictModeException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ToonStrictModeException::class)
        ->and($caught->toonLine)->toBe(2);
});

it('ignores unconsumed lines after the document root in lenient mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode("a: 1\n    b: 2");

    expect($result)->toBe(['a' => 1]);
});

it('throws on duplicate nested object keys in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode("a:\n  b: 1\na:\n  c: 2");
})->throws(ToonStrictModeException::class, "Duplicate key 'a'");

it('throws on duplicate array header keys in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode("a[2]: 1,2\na[1]: 3");
})->throws(ToonStrictModeException::class, "Duplicate key 'a'");

it('includes a line number in tabular row count mismatch errors', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $caught = null;

    try {
        $decoder->decode("t[2]{a,b}:\n  1,2");
    } catch (ToonStrictModeException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ToonStrictModeException::class)
        ->and($caught->toonLine)->toBe(2);
});

it('includes a line number in list item count mismatch errors', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $caught = null;

    try {
        $decoder->decode("x[3]:\n  - 1\n  - 2");
    } catch (ToonStrictModeException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ToonStrictModeException::class)
        ->and($caught->toonLine)->toBe(2);
});

it('decodes a keyed inline tabular header as a list item', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("items[1]:\n  - k[2]{a,b}: 1,2");

    expect($result)->toBe(['items' => [['k' => [['a' => 1, 'b' => 2]]]]]);
});

it('does not path-expand quoted dotted keys', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Safe));

    $result = $decoder->decode('"a.b": 1');

    expect($result)->toBe(['a.b' => 1]);
});

it('expands unquoted dotted keys while keeping quoted ones literal', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Safe));

    $result = $decoder->decode("a.b: 1\n\"c.d\": 2");

    expect($result)->toBe(['a' => ['b' => 1], 'c.d' => 2]);
});
