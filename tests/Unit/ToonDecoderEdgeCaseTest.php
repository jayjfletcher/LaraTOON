<?php

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Decoding\ToonDecoder;
use Jayi\Toon\Enums\PathExpansion;
use Jayi\Toon\Exceptions\ToonStrictModeException;

it('decodes blank lines between object keys', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode("a: 1\n\nb: 2");

    expect($result)->toBe(['a' => 1, 'b' => 2]);
});

it('throws on trailing spaces in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode('name: Ada   ');
})->throws(ToonStrictModeException::class);

it('throws on tab indentation in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode("user:\n\tname: Ada");
})->throws(ToonStrictModeException::class);

it('decodes key with empty value after colon and space', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode('key: ');

    expect($result)->toEqual(['key' => new stdClass]);
});

it('skips lines without colon in non-strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $result = $decoder->decode("name: Ada\nbadline\nrole: admin");

    expect($result)->toBe(['name' => 'Ada', 'role' => 'admin']);
});

it('throws on missing colon in strict mode for object lines', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode("name: Ada\nbadline\nrole: admin");
})->throws(ToonStrictModeException::class);

it('decodes root list array with list items', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[3]:\n  - 1\n  - 2\n  - 3");

    expect($result)->toBe([1, 2, 3]);
});

it('decodes root tabular array', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[2]{a,b}:\n  1,2\n  3,4");

    expect($result)->toBe([
        ['a' => 1, 'b' => 2],
        ['a' => 3, 'b' => 4],
    ]);
});

it('decodes list item with nested array header', function () {
    $decoder = new ToonDecoder;

    $input = "items[1]:\n  - [2]: x,y";

    $result = $decoder->decode($input);

    expect($result)->toBe(['items' => [['x', 'y']]]);
});

it('decodes list item with object that has nested sub-object', function () {
    $decoder = new ToonDecoder;

    $input = "items[1]:\n  - name: Ada\n    meta:\n      role: admin";

    $result = $decoder->decode($input);

    expect($result)->toBe([
        'items' => [
            ['name' => 'Ada', 'meta' => ['role' => 'admin']],
        ],
    ]);
});

it('decodes with auto path expansion when no dotted keys exist', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Auto));

    $result = $decoder->decode("name: Ada\nrole: admin");

    expect($result)->toBe(['name' => 'Ada', 'role' => 'admin']);
});

it('decodes with auto path expansion when dotted keys exist', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Auto));

    $result = $decoder->decode('user.name: Ada');

    expect($result)->toBe(['user' => ['name' => 'Ada']]);
});

it('throws on list item count mismatch in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("[3]:\n  - 1\n  - 2");
})->throws(ToonStrictModeException::class);

it('throws on blank lines inside tabular rows in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("items[2]{id,name}:\n  1,Alice\n\n  2,Bob");
})->throws(ToonStrictModeException::class);

it('throws on blank lines inside array items in strict mode', function () {
    $decoder = new ToonDecoder;

    $decoder->decode("[2]:\n  - 1\n\n  - 2");
})->throws(ToonStrictModeException::class);

it('decodes empty string as empty object', function () {
    // Spec §5: an empty document is an empty object.
    $decoder = new ToonDecoder;

    expect($decoder->decode(''))->toEqual(new stdClass);
});

it('decodes only blank lines as empty object', function () {
    // Spec §5: ignorable blank lines still yield an empty document → empty object.
    $decoder = new ToonDecoder;

    expect($decoder->decode("\n\n\n"))->toEqual(new stdClass);
});

it('detects indent from first indented line', function () {
    $decoder = new ToonDecoder(new DecoderOptions(indentSize: null));

    $result = $decoder->decode("user:\n    id: 1\n    name: Ada");

    expect($result)->toBe(['user' => ['id' => 1, 'name' => 'Ada']]);
});

it('decodes list item that is a plain value', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[3]:\n  - hello\n  - 42\n  - true");

    expect($result)->toBe(['hello', 42, true]);
});

it('decodes inline tabular with fields for single row', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode('items[2]{id,name}: 1,Ada');

    expect($result)->toBe(['items' => [['id' => 1, 'name' => 'Ada']]]);
});

it('decodes nested dotted keys in child objects', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Auto));

    $result = $decoder->decode("parent:\n  child.name: Ada\n  child.role: admin");

    expect($result)->toBe(['parent' => ['child' => ['name' => 'Ada', 'role' => 'admin']]]);
});

it('throws on multiple non-kv root lines in strict mode', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: true));

    $decoder->decode("hello\nworld");
})->throws(ToonStrictModeException::class);

it('decodes root object with array header key', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("items[2]{id,name}:\n  1,Alice\n  2,Bob");

    expect($result)->toBe([
        'items' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
    ]);
});

it('decodes list item with empty dash', function () {
    // Spec §10: a bare dash decodes to an empty object, not an empty array.
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[1]:\n  -");

    expect($result)->toEqual([new stdClass]);
});

it('decodes list item object with empty value key', function () {
    $decoder = new ToonDecoder;

    $result = $decoder->decode("[1]:\n  - key:");

    expect($result)->toEqual([['key' => new stdClass]]);
});

it('decodes list item object with nested object fields', function () {
    $decoder = new ToonDecoder;

    $input = "[1]:\n  - a: 1\n    b: 2\n    c:\n      d: 3";

    $result = $decoder->decode($input);

    expect($result)->toBe([['a' => 1, 'b' => 2, 'c' => ['d' => 3]]]);
});

it('decodes object fields after array in list item', function () {
    $decoder = new ToonDecoder;

    $input = "items[1]:\n  - tags[2]: x,y\n    name: test";

    $result = $decoder->decode($input);

    expect($result)->toBe([
        'items' => [
            ['tags' => ['x', 'y'], 'name' => 'test'],
        ],
    ]);
});

it('decodes list item with nested list array', function () {
    $decoder = new ToonDecoder(new DecoderOptions(strict: false));

    $input = "[1]:\n  - [2]:\n    - a\n    - b";

    $result = $decoder->decode($input);

    expect($result)->toBe([['a', 'b']]);
});

it('decodes object field with empty value after colon in list context', function () {
    $decoder = new ToonDecoder;

    $input = "[1]:\n  - name: Ada\n    items:";

    $result = $decoder->decode($input);

    expect($result)->toEqual([['name' => 'Ada', 'items' => new stdClass]]);
});

it('decodes with path expansion off returns raw dotted keys', function () {
    $decoder = new ToonDecoder(new DecoderOptions(expandPaths: PathExpansion::Off));

    $result = $decoder->decode("a.b: 1\na.c: 2");

    expect($result)->toBe(['a.b' => 1, 'a.c' => 2]);
});
