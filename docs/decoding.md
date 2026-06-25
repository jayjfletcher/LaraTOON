# Decoding

Toon decodes TOON-formatted strings back into PHP arrays with automatic detection of indent size and key expansion.

## Method

### `Toon::decode($toon, $options)`

```php
use Jayi\Toon\Toon;
use Jayi\Toon\Decoding\DecoderOptions;
use Jayi\Toon\Enums\PathExpansion;

// Auto-detect everything (default)
$data = Toon::decode($toon);

// Explicit configuration
$data = Toon::decode($toon, new DecoderOptions(
    strict: true,
    indent: 2,
    expandPaths: PathExpansion::Off,
));
```

## Decoder Options

| Option | Default | Description |
|--------|---------|-------------|
| `strict` | `true` | Enforce count mismatches, indentation, escapes, blank lines in arrays |
| `indent` | `null` | Spaces per level. `null` auto-detects from the first indented line |
| `expandPaths` | `Auto` | `Off`: dotted keys are literal. `Safe`: always expand. `Auto`: expand when dotted keys detected |

## Auto-Detection

When `indent` is `null` (the default), the decoder examines the first indented line in the input to determine the indent size. This works reliably for any consistent indentation (1, 2, 4 spaces, etc.).

When `expandPaths` is set to `Auto`, the decoder checks whether any top-level keys contain dots. If they do, it expands them into nested structures:

```php
$toon = "a.b.c: 1";

// Auto mode (default) — expands because dotted key detected
Toon::decode($toon);
// ['a' => ['b' => ['c' => 1]]]

// Off mode — treats dot as literal key character
Toon::decode($toon, new DecoderOptions(expandPaths: PathExpansion::Off));
// ['a.b.c' => 1]
```

## Strict Mode

With `strict: true` (the default), the decoder validates:

- **Count mismatches** — the declared count in array headers (e.g., `[3]`) must match the actual number of elements
- **Indentation consistency** — all lines within a block must use the same indent level
- **Escape sequences** — only valid escape sequences are accepted
- **Blank lines in arrays** — blank lines within tabular or list arrays are rejected

Set `strict: false` to relax these checks when working with hand-edited or potentially malformed TOON input.
