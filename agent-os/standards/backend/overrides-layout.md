# Third-Party Integration Layout

External-package integrations live under `src/Overrides/<Ecosystem>/<Vendor>/`:

- `src/Overrides/Laravel/Ai/` — laravel/ai (tool results, middleware)
- `src/Overrides/Laravel/Pao/` — laravel/pao (Pest output filter)

Why: code coupled to external classes is quarantined here, so core stays dependency-free and the "works without the framework" guarantee stays visible.

Rules:
- New integration → new vendor subfolder under `Overrides/`.
- NEVER import an external-package class into core `src/` (`Encoding`, `Decoding`, `Toon`, `Enums`). Only `illuminate/support` is a core dep.
