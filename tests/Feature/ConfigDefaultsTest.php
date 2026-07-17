<?php

use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Overrides\Laravel\Pao\Plugin;
use Jayi\Toon\Toon;

it('uses the published config defaults for encoding', function () {
    expect(Toon::encode(['a' => ['b' => 1]]))->toBe("a:\n  b: 1");
});

it('reads indent_size from the toon config', function () {
    config()->set('toon.indent_size', 4);

    expect(Toon::encode(['a' => ['b' => 1]]))->toBe("a:\n    b: 1");
});

it('reads delimiter from the toon config', function () {
    config()->set('toon.delimiter', 'pipe');

    expect(Toon::encode(['tags' => ['a', 'b']]))->toBe('tags[2|]: a|b');
});

it('reads key_folding from the toon config', function () {
    config()->set('toon.key_folding', 'safe');

    expect(Toon::encode(['a' => ['b' => ['c' => 1]]]))->toBe('a.b.c: 1');
});

it('prefers explicit encoder options over the toon config', function () {
    config()->set('toon.indent_size', 4);

    expect(Toon::encode(['a' => ['b' => 1]], new EncoderOptions))->toBe("a:\n  b: 1");
});

it('reads expand_paths from the toon config', function () {
    config()->set('toon.expand_paths', 'off');

    expect(Toon::decode('a.b: 1'))->toBe(['a.b' => 1]);
});

it('reads strict from the toon config', function () {
    config()->set('toon.strict', false);

    expect(Toon::decode("x[3]:\n  - 1\n  - 2"))->toBe(['x' => [1, 2]]);
});

it('prefers explicit decoder options over the toon config', function () {
    config()->set('toon.expand_paths', 'off');

    expect(Toon::decode('a.b: 1', new DecoderOptions))->toBe(['a' => ['b' => 1]]);
});

it('reads pao_output from the toon config', function () {
    config()->set('toon.pao_output', true);

    expect((new Plugin)->outputEnabled())->toBeTrue();

    config()->set('toon.pao_output', false);

    expect((new Plugin)->outputEnabled())->toBeFalse();
});
