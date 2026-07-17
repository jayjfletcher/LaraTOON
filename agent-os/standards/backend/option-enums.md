# Option Enums

Each option enum is the single source for its config vocabulary AND its behavior.

- **String-backed, value = config token** — backing value is the lowercase config string. Config maps straight in via `::from()`, no parallel lookup table.
  - `Delimiter: ',' | "\t" | '|'`, `KeyFolding: 'off'|'safe'`, `PathExpansion: 'off'|'safe'|'auto'`
- **Behavior lives on the enum** — case-varying format logic is a method, not a switch elsewhere.
  ```php
  Delimiter::Comma->headerSymbol();  // '' — default, omitted from headers
  ```
- **Tolerant resolver at the config boundary** (only when aliases/literals needed) — `Delimiter::fromName()` accepts name or literal char, case-insensitive, falls back to `::from()`.

Rules:
- New option enum → string-backed, backing value = the config token.
- Case-varying behavior → method on the enum.
- Add a `fromName`-style resolver only if config accepts aliases or literal chars.
