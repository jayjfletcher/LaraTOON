<?php

use Jayi\Toon\Encoding\NumberEncoder;

it('encodes very large floats without scientific notation', function () {
    $result = NumberEncoder::encode(1e15);
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
    expect($result)->toBe('1000000000000000');
});

it('encodes very large negative floats', function () {
    $result = NumberEncoder::encode(-1e15);
    expect($result)->toStartWith('-');
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
});

it('encodes very small floats without scientific notation', function () {
    $result = NumberEncoder::encode(0.00001);
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
    expect($result)->toBe('0.00001');
});

it('encodes very small negative floats', function () {
    $result = NumberEncoder::encode(-0.00001);
    expect($result)->toStartWith('-');
    expect($result)->not->toContain('e');
});

it('encodes float that formats to scientific notation via sprintf', function () {
    // 1e16 is large enough that sprintf %.14g may produce scientific notation
    $result = NumberEncoder::encode(1e16);
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
});

it('encodes extremely small floats near zero in exponent form (outside canonical range)', function () {
    // §2: outside 1e-6 ≤ |n| < 1e21, MAY emit exponent notation
    $result = NumberEncoder::encode(1e-20);
    expect((float) $result)->toEqual(1e-20);
});

it('encodes negative zero float as zero', function () {
    expect(NumberEncoder::encode(-0.0))->toBe('0');
});

it('encodes float 2.0 without decimal', function () {
    expect(NumberEncoder::encode(2.0))->toBe('2');
});

it('encodes large integer', function () {
    expect(NumberEncoder::encode(PHP_INT_MAX))->toBe((string) PHP_INT_MAX);
});

it('encodes negative integer', function () {
    expect(NumberEncoder::encode(-999))->toBe('-999');
});

it('encodes float in normal range with trailing zeros stripped', function () {
    expect(NumberEncoder::encode(1.10))->toBe('1.1');
    expect(NumberEncoder::encode(100.0))->toBe('100');
});

it('encodes float with many decimal places', function () {
    $result = NumberEncoder::encode(1.123456789012345);
    expect($result)->not->toContain('e');
    // Should be a reasonable decimal representation
    expect((float) $result)->toBeGreaterThan(1.12345);
});

it('encodes float in scientific notation range via sprintf', function () {
    // 1.5e14 is < 1e15 but sprintf %.14g produces scientific notation
    $result = NumberEncoder::encode(1.5e14);
    expect($result)->not->toContain('e');
    expect($result)->not->toContain('E');
});

it('encodes negative small float', function () {
    $result = NumberEncoder::encode(-1.23e-5);
    expect($result)->toBe('-0.0000123');
});

it('encodes very large float in decimal form within canonical range', function () {
    // 1e18 < 1e21, stays in decimal form per §2
    $result = NumberEncoder::encode(1e18);
    expect($result)->toBe('1000000000000000000');
});

it('encodes 1e20 in decimal form within canonical range', function () {
    // 1e20 < 1e21, stays in decimal form per §2
    $result = NumberEncoder::encode(1e20);
    expect($result)->toBe('100000000000000000000');
});

it('encodes small float that rounds to zero', function () {
    // expandSmallFloat where result ends up as '0'
    $result = NumberEncoder::encode(0.0);
    expect($result)->toBe('0');
});

it('encodes negative large float', function () {
    $result = NumberEncoder::encode(-1.5e18);
    expect($result)->toStartWith('-');
    expect($result)->not->toContain('e');
});
