# Core Works Without Laravel

Core encode/decode must run in any PHP context (plain scripts, tests, non-Laravel). Framework + optional integrations layer on top, never required.

- `Toon::laravelConfig()` returns `null` when no container / no `config` bound — code falls back to package defaults.
- Optional integrations are gated by `class_exists` at the wiring point (boot/macro), NEVER added to composer `require`:

```php
if (class_exists('\Laravel\Mcp\Response')) {
    McpResponse::macro('toon', fn (mixed $c) => McpResponse::text(Toon::smart($c)));
}
```

Rules:
- New optional dep (MCP, PAO, laravel/ai): guard with `class_exists`, keep it in `require-dev` or unlisted — not `require`.
- Only hard framework dep is `illuminate/support`.
