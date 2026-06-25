<?php

use Jayi\Toon\Overrides\Laravel\Ai\ToonToolResponse;

it('implements Stringable', function () {
    $response = new ToonToolResponse(['id' => 1, 'name' => 'Ada']);

    expect($response)->toBeInstanceOf(Stringable::class);
});

it('encodes simple object to toon string', function () {
    $response = new ToonToolResponse(['id' => 1, 'name' => 'Ada']);

    expect((string) $response)->toBe("id: 1\nname: Ada");
});

it('encodes nested data using smart encoding', function () {
    $response = new ToonToolResponse([
        'a' => ['b' => ['c' => ['d' => 1]]],
    ]);

    // Smart should pick compact for deeply nested data
    expect((string) $response)->toBe('a.b.c.d: 1');
});

it('encodes flat data using default encoding', function () {
    $response = new ToonToolResponse(['x' => 1, 'y' => 2]);

    expect((string) $response)->toBe("x: 1\ny: 2");
});

it('encodes array with tabular data', function () {
    $response = new ToonToolResponse([
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
    ]);

    $result = (string) $response;

    expect($result)->toContain('users[2]{id,name}:');
    expect($result)->toContain('1,Alice');
    expect($result)->toContain('2,Bob');
});

it('can be cast to string multiple times with same result', function () {
    $response = new ToonToolResponse(['key' => 'value']);

    $first = (string) $response;
    $second = (string) $response;

    expect($first)->toBe($second);
    expect($first)->toBe('key: value');
});
