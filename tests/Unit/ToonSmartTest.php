<?php

use Jayi\Toon\Toon;

it('smart returns default for list array', function () {
    $data = [1, 2, 3];

    $smart = Toon::smart($data);
    $default = Toon::encode($data);

    expect($smart)->toBe($default);
});

it('smart returns default for scalar input', function () {
    expect(Toon::smart('hello'))->toBe('hello');
    expect(Toon::smart(42))->toBe('42');
});

it('smart picks compact for foldable single-key chains', function () {
    // A structure with a single-key assoc containing another single-key assoc
    $data = ['wrapper' => ['inner' => ['value' => 42]]];

    $result = Toon::smart($data);

    // Should use compact (key folding) since it has foldable structure
    expect($result)->toBe('wrapper.inner.value: 42');
});

it('smart picks default for assoc with no depth or foldable keys', function () {
    $data = ['a' => 1, 'b' => 2, 'c' => 3];

    $smart = Toon::smart($data);
    $default = Toon::encode($data);

    expect($smart)->toBe($default);
});

it('smart picks compact for deeply nested assoc at depth 4+', function () {
    $data = ['l1' => ['l2' => ['l3' => ['l4' => ['value' => 1]]]]];

    $smart = Toon::smart($data);
    $compact = Toon::compact($data);

    expect($smart)->toBe($compact);
});

it('inspectStructure skips list arrays in depth check', function () {
    // Mixed data: assoc with list values -- lists should not increase depth
    $data = ['items' => [1, 2, 3], 'name' => 'test'];

    $smart = Toon::smart($data);
    $default = Toon::encode($data);

    expect($smart)->toBe($default);
});
