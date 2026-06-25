<?php

use Jayi\Toon\Decoding\HeaderParser;
use Jayi\Toon\Enums\Delimiter;

it('parses a simple array header', function () {
    $result = HeaderParser::parse('tags[3]: a,b,c');

    expect($result)->not->toBeNull();
    expect($result['key'])->toBe('tags');
    expect($result['length'])->toBe(3);
    expect($result['delimiter'])->toBe(Delimiter::Comma);
    expect($result['fields'])->toBeNull();
    expect($result['inline'])->toBe('a,b,c');
});

it('parses a tabular header with fields', function () {
    $result = HeaderParser::parse('items[2]{id,name}:');

    expect($result)->not->toBeNull();
    expect($result['key'])->toBe('items');
    expect($result['length'])->toBe(2);
    expect($result['fields'])->toBe(['id', 'name']);
    expect($result['inline'])->toBeNull();
});

it('parses a root array header without key', function () {
    $result = HeaderParser::parse('[3]: a,b,c');

    expect($result)->not->toBeNull();
    expect($result['key'])->toBeNull();
    expect($result['length'])->toBe(3);
    expect($result['inline'])->toBe('a,b,c');
});

it('returns null for empty line', function () {
    expect(HeaderParser::parse(''))->toBeNull();
    expect(HeaderParser::parse('   '))->toBeNull();
});

it('returns null for line without brackets', function () {
    expect(HeaderParser::parse('name: Ada'))->toBeNull();
});

it('returns null for unclosed bracket', function () {
    expect(HeaderParser::parse('items[3'))->toBeNull();
});

it('returns null for non-numeric bracket content', function () {
    expect(HeaderParser::parse('items[abc]:'))->toBeNull();
});

it('returns null when no colon follows bracket', function () {
    expect(HeaderParser::parse('items[3]'))->toBeNull();
});

it('parses pipe delimiter', function () {
    $result = HeaderParser::parse('data[2|]: a|b');

    expect($result)->not->toBeNull();
    expect($result['delimiter'])->toBe(Delimiter::Pipe);
    expect($result['inline'])->toBe('a|b');
});

it('parses tab delimiter', function () {
    $result = HeaderParser::parse("data[2\t]: a\tb");

    expect($result)->not->toBeNull();
    expect($result['delimiter'])->toBe(Delimiter::Tab);
});

it('parses quoted key in header', function () {
    $result = HeaderParser::parse('"my-key"[3]: 1,2,3');

    expect($result)->not->toBeNull();
    expect($result['key'])->toBe('my-key');
    expect($result['length'])->toBe(3);
});

it('parses header with quoted fields', function () {
    $result = HeaderParser::parse('items[2]{"field-a","field-b"}:');

    expect($result)->not->toBeNull();
    expect($result['fields'])->toBe(['field-a', 'field-b']);
});

it('returns null when text appears between bracket and brace', function () {
    expect(HeaderParser::parse('items[2] junk {id,name}:'))->toBeNull();
});

it('returns null for unclosed brace', function () {
    expect(HeaderParser::parse('items[2]{id,name:'))->toBeNull();
});

it('splits delimited content respecting quotes', function () {
    $parts = HeaderParser::splitDelimited('a,"b,c",d', Delimiter::Comma);

    expect($parts)->toBe(['a', '"b,c"', 'd']);
});

it('splits pipe-delimited content', function () {
    $parts = HeaderParser::splitDelimited('a|b|c', Delimiter::Pipe);

    expect($parts)->toBe(['a', 'b', 'c']);
});

it('handles escaped quotes in split', function () {
    $parts = HeaderParser::splitDelimited('"a\\"b",c', Delimiter::Comma);

    expect($parts)->toHaveCount(2);
    expect($parts[0])->toBe('"a\\"b"');
    expect($parts[1])->toBe('c');
});

it('parses header with inline but no space after colon', function () {
    $result = HeaderParser::parse('[3]:a,b,c');

    expect($result)->not->toBeNull();
    expect($result['inline'])->toBe('a,b,c');
});

it('parses empty array header', function () {
    $result = HeaderParser::parse('items[0]:');

    expect($result)->not->toBeNull();
    expect($result['key'])->toBe('items');
    expect($result['length'])->toBe(0);
    expect($result['inline'])->toBeNull();
});
