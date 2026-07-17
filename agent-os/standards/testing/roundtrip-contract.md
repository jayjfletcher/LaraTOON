# Roundtrip Is The Contract

Primary guarantee: no data loss. Central assertion —

```php
expect($decoder->decode($encoder->encode($data)))->toBe($data);   // strict mode
```

- Roundtrip proves losslessness across any structure. Exact-TOON-string assertions are brittle — use them only for format specifics (spacing, header form), not as the main coverage.
- Local `roundtrip()` helper wraps encode→decode; curated `->with()` datasets feed it gnarly structures.
- New supported type/structure → add a roundtrip dataset row (`decode(encode($x)) === $x`, strict) FIRST, then any format-specific tests.
