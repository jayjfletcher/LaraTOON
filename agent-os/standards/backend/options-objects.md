# Config via Readonly Options Objects

Tuning goes through `readonly` Options classes, not encode/decode method params:

- `EncoderOptions` (indentSize, delimiter, keyFolding, flattenDepth)
- `DecoderOptions` (strict, indentSize, expandPaths)

Why: immutable — safe to share/reuse without mutation bugs; new options don't break method signatures.

Rules:
- Options are `readonly` with named-arg ctor + defaults.
- Expose common combos as named factory presets: `EncoderOptions::compact()`.
- `encode()`/`decode()` take data only; config comes from the Options passed to the ctor.
- New knob → default to adding it on the Options ctor; a method param is a case-by-case exception.

```php
$enc = new ToonEncoder(EncoderOptions::compact());          // preset
$dec = new ToonDecoder(new DecoderOptions(strict: false));  // named arg
```
