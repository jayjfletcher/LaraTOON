# Overrides Adapt, Then Delegate

An override's only job: bridge the host framework's extension point to `Toon`. Zero encoding/format logic in overrides.

Each override adapts one host hook → `Toon::encode`/`Toon::smart`:

- `EncodesToonToolResults` trait — `handle()` runs tool, wraps result in `ToonToolResponse`
- `ToonToolResponse` — `Stringable`, `__toString()` returns `Toon::smart($data)`
- `ToonOutputFilter` — stream filter, re-encodes via `Toon::encode($decoded)`
- `ToonMiddleware` — appends format instructions to the prompt (no encoding, but still just adapts the hook)

```php
public function __construct(mixed $data) { $this->encoded = Toon::smart($data); }
```

Rule: new override → adapt the host contract, call `Toon::*`. Don't reimplement encoding. Same principle as [[thin-facade]] and [[totoon-surface]].
