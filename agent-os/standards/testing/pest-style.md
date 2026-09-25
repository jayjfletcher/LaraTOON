# Pest Style

Observed convention across the suite (follow it, not strictly enforced):

- `it('does x', fn () => ...)` — `test()` used rarely, `describe()` not used.
- `->toBe()` for assertions — strict identity/type-exact. `->toEqual()` only when loose equality is genuinely intended (rare; ~12 uses vs ~250 `toBe`).

```php
it('roundtrips a tabular array', function () {
    expect(roundtrip($data))->toBe($data);
});
```
