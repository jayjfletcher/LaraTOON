# Configuration

Toon ships with sensible defaults but every option is configurable via the publishable config file.

## Publishing the Config

```bash
php artisan vendor:publish --tag=toon-config
```

This creates `config/toon.php` in your application.

## Options

```php
// config/toon.php
return [
    'indent_size' => 2,
    'delimiter' => 'comma',
    'key_folding' => 'off',
    'flatten_depth' => INF,
    'strict' => true,
    'expand_paths' => 'off',
    'pao_output' => env('TOON_PAO_OUTPUT', false),
];
```

### Encoding Options

| Key | Default | Values | Description |
|-----|---------|--------|-------------|
| `indent_size` | `2` | Any positive integer | Spaces per indentation level |
| `delimiter` | `'comma'` | `'comma'`, `'tab'`, `'pipe'` | Value separator in arrays |
| `key_folding` | `'off'` | `'off'`, `'safe'` | Collapse single-key chains to dotted paths |
| `flatten_depth` | `INF` | Any positive integer or `INF` | Max segments to fold when key folding is enabled |

### Decoding Options

| Key | Default | Values | Description |
|-----|---------|--------|-------------|
| `strict` | `true` | `true`, `false` | Enforce strict validation during decoding |
| `expand_paths` | `'off'` | `'off'`, `'safe'`, `'auto'` | Dotted key expansion strategy |

### PAO Integration

| Key | Default | Values | Description |
|-----|---------|--------|-------------|
| `pao_output` | `false` | `true`, `false` | Encode PAO test output as TOON when an AI agent is detected |

Set via the `TOON_PAO_OUTPUT` environment variable or directly in the config file.

## Runtime Override

Config values provide defaults. You can always override per-call by passing an options object:

```php
use Jayi\Toon\Toon;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Enums\Delimiter;

// Uses config defaults
Toon::encode($data);

// Overrides delimiter for this call only
Toon::encode($data, new EncoderOptions(delimiter: Delimiter::Pipe));
```
