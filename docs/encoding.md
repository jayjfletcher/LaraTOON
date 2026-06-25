# Encoding

Toon provides three encoding methods and a flexible options object for controlling output.

## Methods

### `Toon::encode($data, $options)`

Standard encoding with configurable options.

```php
use Jayi\Toon\Toon;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Enums\Delimiter;

$toon = Toon::encode($data);
$toon = Toon::encode($data, new EncoderOptions(indentSize: 4));
$toon = Toon::encode($data, new EncoderOptions(delimiter: Delimiter::Pipe));
```

### `Toon::compact($data)`

Encodes with settings optimized for maximum token savings: 1-space indent and key folding enabled.

```php
$toon = Toon::compact([
    'data' => ['meta' => ['items' => ['x', 'y']]],
]);
// Output: data.meta.items[2]: x,y
```

### `Toon::smart($data)`

Automatically chooses compact or default encoding based on the data structure. Uses compact when it detects foldable nested chains or deep nesting (depth >= 4); uses default otherwise.

```php
$toon = Toon::smart($data); // picks the smallest output
```

## Encoder Options

All options are passed via the `EncoderOptions` value object:

```php
use Jayi\Toon\Encoding\EncoderOptions;

$options = new EncoderOptions(
    indentSize: 2,
    delimiter: Delimiter::Comma,
    keyFolding: KeyFolding::Off,
    flattenDepth: INF,
);
```

| Option | Default | Description |
|--------|---------|-------------|
| `indentSize` | `2` | Spaces per indentation level |
| `delimiter` | `Comma` | Value separator: `Comma`, `Tab`, or `Pipe` |
| `keyFolding` | `Off` | `Off` or `Safe`. When Safe, collapses single-key chains to dotted paths |
| `flattenDepth` | `INF` | Max segments to fold when key folding is enabled |

### Presets

```php
EncoderOptions::compact(); // indentSize=1, keyFolding=Safe
```

## Type Normalization

The encoder automatically normalizes common PHP types before encoding:

- `DateTime` / `DateTimeImmutable` / `Carbon` instances are converted to ISO 8601 strings
- Backed enums are converted to their value
- Unit enums are converted to their name
- Objects implementing `JsonSerializable` are serialized via `jsonSerialize()`
- Objects implementing `Arrayable` (Laravel) are converted via `toArray()`
- `Stringable` objects are cast to string

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

// Compare with compact encoding
$stats = Toon::savings($data, EncoderOptions::compact());
```
