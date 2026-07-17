<?php

use Jayi\Toon\Encoding\ValueEncoder;
use Jayi\Toon\Enums\Delimiter;

it('encodes null', function () {
    expect(ValueEncoder::encode(null, Delimiter::Comma, Delimiter::Comma))->toBe('null');
});

it('encodes booleans', function () {
    expect(ValueEncoder::encode(true, Delimiter::Comma, Delimiter::Comma))->toBe('true');
    expect(ValueEncoder::encode(false, Delimiter::Comma, Delimiter::Comma))->toBe('false');
});

it('encodes integers', function () {
    expect(ValueEncoder::encode(42, Delimiter::Comma, Delimiter::Comma))->toBe('42');
});

it('encodes strings that do not need quoting', function () {
    expect(ValueEncoder::encode('hello', Delimiter::Comma, Delimiter::Comma))->toBe('hello');
    expect(ValueEncoder::encode('Hello World', Delimiter::Comma, Delimiter::Comma))->toBe('Hello World');
});

it('quotes empty strings', function () {
    expect(ValueEncoder::encode('', Delimiter::Comma, Delimiter::Comma))->toBe('""');
});

it('quotes strings with leading/trailing whitespace', function () {
    expect(ValueEncoder::encode(' hello', Delimiter::Comma, Delimiter::Comma))->toBe('" hello"');
    expect(ValueEncoder::encode('hello ', Delimiter::Comma, Delimiter::Comma))->toBe('"hello "');
});

it('quotes reserved words', function () {
    expect(ValueEncoder::encode('true', Delimiter::Comma, Delimiter::Comma))->toBe('"true"');
    expect(ValueEncoder::encode('false', Delimiter::Comma, Delimiter::Comma))->toBe('"false"');
    expect(ValueEncoder::encode('null', Delimiter::Comma, Delimiter::Comma))->toBe('"null"');
});

it('quotes numeric-like strings', function () {
    expect(ValueEncoder::encode('42', Delimiter::Comma, Delimiter::Comma))->toBe('"42"');
    expect(ValueEncoder::encode('-3.14', Delimiter::Comma, Delimiter::Comma))->toBe('"-3.14"');
    expect(ValueEncoder::encode('1e6', Delimiter::Comma, Delimiter::Comma))->toBe('"1e6"');
    expect(ValueEncoder::encode('05', Delimiter::Comma, Delimiter::Comma))->toBe('"05"');
});

it('quotes strings containing colons', function () {
    expect(ValueEncoder::encode('http://example.com', Delimiter::Comma, Delimiter::Comma))->toBe('"http://example.com"');
});

it('quotes strings containing the active delimiter', function () {
    expect(ValueEncoder::encode('a,b', Delimiter::Comma, Delimiter::Comma))->toBe('"a,b"');
    expect(ValueEncoder::encode('a|b', Delimiter::Pipe, Delimiter::Pipe))->toBe('"a|b"');
});

it('quotes strings starting with hyphen', function () {
    expect(ValueEncoder::encode('-', Delimiter::Comma, Delimiter::Comma))->toBe('"-"');
    expect(ValueEncoder::encode('-hello', Delimiter::Comma, Delimiter::Comma))->toBe('"-hello"');
});

it('quotes strings with brackets and braces', function () {
    expect(ValueEncoder::encode('[1]', Delimiter::Comma, Delimiter::Comma))->toBe('"[1]"');
    expect(ValueEncoder::encode('{a}', Delimiter::Comma, Delimiter::Comma))->toBe('"{a}"');
});

it('escapes backslashes and quotes', function () {
    expect(ValueEncoder::encode('a\\b', Delimiter::Comma, Delimiter::Comma))->toBe('"a\\\\b"');
    expect(ValueEncoder::encode('a"b', Delimiter::Comma, Delimiter::Comma))->toBe('"a\\"b"');
});

it('escapes control characters', function () {
    expect(ValueEncoder::encode("line1\nline2", Delimiter::Comma, Delimiter::Comma))->toBe('"line1\\nline2"');
    expect(ValueEncoder::encode("col1\tcol2", Delimiter::Comma, Delimiter::Comma))->toBe('"col1\\tcol2"');
});

it('does not quote unicode and emoji', function () {
    expect(ValueEncoder::encode('Hello 世界 👋', Delimiter::Comma, Delimiter::Comma))->toBe('Hello 世界 👋');
});

it('encodes keys that match identifier pattern as unquoted', function () {
    expect(ValueEncoder::encodeKey('name'))->toBe('name');
    expect(ValueEncoder::encodeKey('user_id'))->toBe('user_id');
});

it('quotes keys that do not match identifier pattern', function () {
    expect(ValueEncoder::encodeKey('my-key'))->toBe('"my-key"');
    expect(ValueEncoder::encodeKey('has space'))->toBe('"has space"');
    expect(ValueEncoder::encodeKey(''))->toBe('""');
});

it('quotes literal dotted keys so they are not mistaken for folded paths', function () {
    expect(ValueEncoder::encodeKey('data.meta'))->toBe('"data.meta"');
    expect(ValueEncoder::encodeKey('a.b'))->toBe('"a.b"');
});
