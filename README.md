# Toon

A PHP Laravel package for encoding and decoding [TOON (Token-Oriented Object Notation)](https://toonformat.dev) v3.0.

TOON is a line-oriented, indentation-based text format that encodes the JSON data model with explicit structure and minimal quoting. It is particularly efficient for arrays of uniform objects, reducing token count by 35-58% compared to JSON.

## Documentation

| Document | Description |
|----------|-------------|
| [Encoding](docs/encoding.md) | Encoding methods, options, presets, and type normalization |
| [Decoding](docs/decoding.md) | Decoding method, options, auto-detection, and strict mode |
| [Laravel Integration](docs/laravel-integration.md) | Collection, Builder, and JsonResponse macros, HasToon trait |
| [AI Integration](docs/ai-integration.md) | Agent middleware, tool trait, MCP response macro, PAO output |
| [Format Reference](docs/format-reference.md) | TOON format overview with all structure types |
| [Configuration](docs/configuration.md) | Config options and publishing |

## Installation

The package is included via Composer path repository:

```bash
composer require jayi/toon
```

The service provider and facade are auto-discovered.

## Quick Start

```php
use Jayi\Toon\Toon;

// Encode
$toon = Toon::encode([
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
    ],
]);

// Output:
// users[2]{id,name,role}:
//   1,Alice,admin
//   2,Bob,user

// Decode
$data = Toon::decode($toon);
```

Three encoding modes are available:

```php
Toon::encode($data);          // Standard encoding
Toon::compact($data);         // Max token savings (1-space indent, key folding)
Toon::smart($data);           // Auto-selects best encoding
```

## Token Savings

Estimate how much TOON saves compared to JSON:

```php
$stats = Toon::savings($data);
// [
//     'json_chars' => 238,
//     'toon_chars' => 150,
//     'saved_chars' => 88,
//     'saved_percent' => 37.0,
// ]
```

### Typical Savings

| Data Shape | Default | Compact |
|------------|---------|---------|
| Tabular (3 rows) | ~46% | ~47% |
| Large tabular (10 rows) | ~56% | ~58% |
| Deeply nested | -8% | ~57% |
| Mixed (tabular + nested + lists) | ~35% | ~37% |
| Flat object | ~16% | ~16% |

Compact mode makes the biggest difference on deeply nested data due to key folding.

## Testing

```bash
php artisan test packages/Toon/tests
```
