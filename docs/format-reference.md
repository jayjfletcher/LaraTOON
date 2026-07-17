# TOON Format Reference

TOON (Token-Oriented Object Notation) v3.3 is a line-oriented, indentation-based text format that encodes the JSON data model with explicit structure and minimal quoting.

For the full specification, see [toonformat.dev/reference/spec.md](https://toonformat.dev/reference/spec.md).

## Objects

Key-value pairs, one per line:

```
id: 123
name: Ada
active: true
```

## Nested Objects

Indent child keys under the parent:

```
user:
  id: 123
  name: Ada
```

## Primitive Arrays (Inline)

Short arrays of scalars are written inline with a count header:

```
tags[3]: admin,ops,dev
```

The `[3]` declares the element count. Values are separated by the active delimiter (comma by default).

## Tabular Arrays (Uniform Objects)

Arrays of objects sharing the same keys use a compact tabular format:

```
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
```

The header declares the row count and column names. Each indented line is one row with values in column order.

## Mixed Arrays (List Items)

Arrays containing mixed types or non-uniform objects use dash-prefixed list items:

```
items[3]:
  - 1
  - text
  - true
```

## Key Folding (Compact Mode)

When key folding is enabled (`keyFolding: Safe`), single-child object chains are collapsed into dotted paths:

```
a.b.c: 1
data.meta.items[2]: x,y
```

This is equivalent to the nested form:

```
a:
  b:
    c: 1
data:
  meta:
    items[2]: x,y
```

Key folding produces significant token savings on deeply nested data.

## Alternate Delimiters

The default delimiter is comma. Tab and pipe are also supported. The delimiter is indicated in the array header:

### Pipe Delimiter

```
items[2|]{id|name}:
  1|Alice
  2|Bob
```

### Tab Delimiter

```
items[2	]{id	name}:
  1	Alice
  2	Bob
```

The delimiter character appears after the count in the header bracket (e.g., `[2|]` for pipe).

## Special Values

| Value | TOON Representation |
|-------|-------------------|
| `null` | `null` |
| `true` | `true` |
| `false` | `false` |
| Empty string | `""` (quoted, to distinguish from an empty object) |
| Empty object | `key:` (bare colon, no value or children) |
| Empty array | `[]` |

## Escaping

Values containing the active delimiter, leading/trailing whitespace, or literal `null`/`true`/`false` strings are escaped with surrounding quotes. Newlines within values use `\n` escape sequences.
