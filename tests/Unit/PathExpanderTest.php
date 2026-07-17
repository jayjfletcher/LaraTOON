<?php

use Jayi\Toon\Decoding\PathExpander;
use Jayi\Toon\Exceptions\ToonStrictModeException;

it('expands dotted keys into nested structures', function () {
    $expander = new PathExpander;

    $result = $expander->expand(['a.b' => 1, 'a.c' => 2]);

    expect($result)->toBe(['a' => ['b' => 1, 'c' => 2]]);
});

it('does not expand non-identifier segments', function () {
    $expander = new PathExpander;

    $result = $expander->expand(['123.abc' => 1]);

    expect($result)->toBe(['123.abc' => 1]);
});

it('does not expand keys without dots', function () {
    $expander = new PathExpander;

    $result = $expander->expand(['name' => 'Ada']);

    expect($result)->toBe(['name' => 'Ada']);
});

it('returns scalar data unchanged', function () {
    $expander = new PathExpander;

    expect($expander->expand('hello'))->toBe('hello');
    expect($expander->expand(42))->toBe(42);
    expect($expander->expand(null))->toBeNull();
    expect($expander->expand(true))->toBeTrue();
});

it('expands list arrays recursively', function () {
    $expander = new PathExpander;

    $result = $expander->expand([
        ['a.b' => 1],
        ['a.c' => 2],
    ]);

    expect($result)->toBe([
        ['a' => ['b' => 1]],
        ['a' => ['c' => 2]],
    ]);
});

it('deep merges nested objects at same path', function () {
    $expander = new PathExpander;

    $result = $expander->expand([
        'a.b' => ['x' => 1],
        'a.c' => 2,
        'a.b.y' => 3,
    ]);

    expect($result)->toBe(['a' => ['b' => ['x' => 1, 'y' => 3], 'c' => 2]]);
});

it('throws on path conflict in strict mode', function () {
    $expander = new PathExpander(strict: true);

    $expander->expand(['a.b' => 1, 'a' => 2]);
})->throws(ToonStrictModeException::class);

it('overwrites on path conflict in non-strict mode', function () {
    $expander = new PathExpander(strict: false);

    $result = $expander->expand(['a.b' => 1, 'a' => 2]);

    expect($result)->toBe(['a' => 2]);
});

it('handles conflict where intermediate path is a list array in non-strict mode', function () {
    $expander = new PathExpander(strict: false);

    // First set a.b to a list, then try to expand a.b.c
    $result = $expander->expand(['a' => ['b' => [1, 2, 3]], 'a.b.c' => 'override']);

    expect($result['a']['b']['c'])->toBe('override');
});

it('throws on duplicate key at leaf in strict mode', function () {
    $expander = new PathExpander(strict: true);

    // Two different dotted paths that converge on same leaf
    $result = $expander->expand(['a.b' => ['x' => 1], 'a' => ['b' => ['x' => 2]]]);
})->throws(ToonStrictModeException::class);

it('deep merges objects at leaf level', function () {
    $expander = new PathExpander(strict: false);

    $data = [
        'a' => ['x' => 1],
        'a.y' => 2,
    ];

    $result = $expander->expand($data);

    expect($result)->toBe(['a' => ['x' => 1, 'y' => 2]]);
});
