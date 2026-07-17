# Thin Static Entry Point

`Toon` is the public API + facade target. It wires config and delegates; it holds no per-value logic.

- Encode/decode logic lives in `ToonEncoder`/`ToonDecoder` instances — testable, usable without the static wrapper.
- `Toon::encode/decode` just build an encoder/decoder and call it.
- Allowed in `Toon`: cross-cutting orchestration/heuristics — `smart()`, `savings()`, `shouldUseCompact()`. Per-value encode/decode logic NEVER leaks here.
- `Facades\Toon` docblock `@method` lines mirror the static API exactly — update both together.

```php
public static function encode(mixed $data, ?EncoderOptions $options = null): string
{
    return (new ToonEncoder($options ?? self::defaultEncoderOptions()))->encode($data);
}
```
