# Normalize Before Encoding

`ToonEncoder::encode()` runs one `normalize()` pass before emitting any TOON.

After normalize, downstream code sees ONLY: `null`, `bool`, `int`, `float`, `string`, `array` (list or map). All type coercion lives in `normalize()` — one place to reason about and test.

normalize() maps:
- `DateTimeInterface` → ISO-8601 string (`format('c')`)
- `BackedEnum` → `->value`; `UnitEnum` → `->name`
- `JsonSerializable` → `jsonSerialize()` (recursed)
- `Stringable` → `(string)`
- `Traversable` / object → array (recursed)
- non-finite float (NaN/Inf) → `null`; `-0.0` → `0`; integral float → `int`

Rules:
- New type handling goes in `normalize()` — NEVER in `encodeValue()`/emit paths. Adding coercion downstream breaks the primitives-only invariant.
- Any new float path must send NaN/Infinity to `null`, or output is invalid TOON.
