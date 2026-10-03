# Exception Hierarchy

All package errors descend from one base — callers catch broad or narrow, and never see a raw SPL exception.

```
ToonException (extends RuntimeException)
├── ToonEncodeException
└── ToonDecodeException        ← readonly ?int $toonLine
    └── ToonStrictModeException
```

- `ToonException` lives in `src/Exceptions/` (shared by every domain); each subclass lives in its domain's `Exceptions/` (`Domains/Encoding/Exceptions`, `Domains/Decoding/Exceptions`).
- Catch `ToonException` for anything from the package; narrow to `Encode` / `Decode` / `StrictMode` as needed.
- `StrictModeException` extends `DecodeException` — strict violations catch as decode errors, or singled out.
- `ToonDecodeException` carries `?int $toonLine` and prepends `"Line N: "` to the message. Decode-side throws pass the offending line so failures are locatable in the source TOON.

Guidance:
- Prefer a `ToonException` subclass over a raw `\RuntimeException` / `\InvalidArgumentException` for package errors.
- On the decode path, pass `$line` when you have it. Strict-only violations → `ToonStrictModeException`.
