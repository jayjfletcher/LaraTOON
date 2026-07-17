# Config Feeds Defaults Only

Laravel `config/toon.php` supplies DEFAULT options. Caller-passed options always win.

```php
new ToonEncoder($options ?? self::defaultEncoderOptions());  // caller intent authoritative
```

- If a caller passes `EncoderOptions`/`DecoderOptions`, use them as-is. Config is the fallback for when they pass nothing.
- Config is read through the container (`self::laravelConfig()`) and returns `null` outside a booted Laravel app.
- Adding a config key: map it into the Options ctor AND supply a hard default for the null-config path (outside Laravel). Cast + `?? default` both required.

```php
indentSize: (int) ($config['indent_size'] ?? 2),   // cast + default
```
