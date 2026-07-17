<?php

use Jayi\Toon\Encoding\NumberEncoder;

it('encodes integers', function () {
    expect(NumberEncoder::encode(0))->toBe('0');
    expect(NumberEncoder::encode(42))->toBe('42');
    expect(NumberEncoder::encode(-1))->toBe('-1');
    expect(NumberEncoder::encode(1000000))->toBe('1000000');
});

it('encodes floats without trailing zeros', function () {
    expect(NumberEncoder::encode(1.5))->toBe('1.5');
    expect(NumberEncoder::encode(3.14))->toBe('3.14');
    expect(NumberEncoder::encode(0.1))->toBe('0.1');
});

it('normalizes negative zero to zero', function () {
    expect(NumberEncoder::encode(-0.0))->toBe('0');
});

it('converts NaN to null', function () {
    expect(NumberEncoder::encode(NAN))->toBeNull();
});

it('converts Infinity to null', function () {
    expect(NumberEncoder::encode(INF))->toBeNull();
    expect(NumberEncoder::encode(-INF))->toBeNull();
});

it('avoids scientific notation for large numbers', function () {
    expect(NumberEncoder::encode(1e6))->toBe('1000000');
});

it('avoids scientific notation for small numbers', function () {
    $result = NumberEncoder::encode(0.0001);
    expect($result)->toBe('0.0001');
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
});

it('removes trailing zeros from fractional part', function () {
    expect(NumberEncoder::encode(1.50))->toBe('1.5');
    expect(NumberEncoder::encode(2.0))->toBe('2');
});

it('preserves full float precision', function () {
    expect(NumberEncoder::encode(1.0000000000000002))->toBe('1.0000000000000002');
    expect(NumberEncoder::encode(0.30000000000000004))->toBe('0.30000000000000004');
    expect(NumberEncoder::encode(-1.0000000000000002))->toBe('-1.0000000000000002');
});

it('roundtrips 17 significant digit floats through string form', function () {
    $values = [1.0000000000000002, 0.1 + 0.2, 1.7976931348623157e308, 2.2250738585072014e-308];

    foreach ($values as $value) {
        expect((float) NumberEncoder::encode($value))->toBe($value);
    }
});
