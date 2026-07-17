# Unit vs Feature Tests

- `tests/Unit/` — plain PHP, instantiate `ToonEncoder`/`ToonDecoder` directly. No `TestCase`, no Laravel boot. `Pest.php` binds `TestCase` only to `Feature`.
- `tests/Feature/` — boots Testbench + `ToonServiceProvider` (facade, macros, config).

Placement: put a test in `Feature` when it needs the container/config/macros/service provider; otherwise `Unit`. Judgment call, but don't pull Testbench into a test that doesn't use it.

```php
// tests/Pest.php
uses(TestCase::class)->in('Feature');   // Unit stays framework-free
```
