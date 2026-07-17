# Uniform `toToon()` Surface

Every type that can encode itself exposes the SAME method — learn once, use everywhere:

```php
toToon(?EncoderOptions $options = null): string
```

Registered on:
- `Collection`, `Builder`, `JsonResponse` — macros in `ToonServiceProvider::boot()`
- Any class with `toArray()` — `HasToon` trait

Each impl: adapt source → array/data, then delegate. NO encoding logic in the macro/trait.

```php
Collection::macro('toToon', fn (?EncoderOptions $options = null) => Toon::encode($this->all(), $options));
Builder::macro('toToon', fn (?EncoderOptions $options = null) => Toon::encode($this->get()->toArray(), $options));
```

Rule: new `toToon` on a type → same signature, convert to array, `return Toon::encode($data, $options)`.
