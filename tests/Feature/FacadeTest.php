<?php

use Jayi\Toon\Facades\Toon;

test('it encodes data via facade', function () {
    $result = Toon::encode(['id' => 1, 'name' => 'Ada']);

    expect($result)->toBe("id: 1\nname: Ada");
});

test('it decodes data via facade', function () {
    $result = Toon::decode("id: 1\nname: Ada");

    expect($result)->toBe(['id' => 1, 'name' => 'Ada']);
});

test('it compacts data via facade', function () {
    $result = Toon::compact(['parent' => ['child' => 'value']]);

    expect($result)->toBe('parent.child: value');
});

test('it smart encodes via facade', function () {
    $result = Toon::smart(['a' => ['b' => ['c' => ['d' => 1]]]]);

    expect($result)->toBe('a.b.c.d: 1');
});

test('it calculates savings via facade', function () {
    $result = Toon::savings(['id' => 1, 'name' => 'Ada']);

    expect($result)->toHaveKeys(['json_chars', 'toon_chars', 'saved_chars', 'saved_percent']);
});
