# Key Folding ⇄ Path Expansion

`KeyFolder::fold()` (encode) and `PathExpander::expand()` (decode) are INVERSE ops. Roundtrip depends on their symmetry.

- Fold collapses single-key nested chains → dotted path (`{"a":{"b":1}}` → `a.b: 1`).
- Expand reverses it with deep merge (`{"a.b":1,"a.c":2}` → `{"a":{"b":1,"c":2}}`).
- Both gate on the SAME `IDENTIFIER_PATTERN` (`^[A-Za-z_][A-Za-z0-9_]*$`) — only identifier segments fold/expand.

**Symmetry is load-bearing.** Change one side's rule without the other → roundtrip silently breaks (data folds but won't expand back, or literal dotted keys get mangled). Edit both together + add a [[roundtrip-contract]] case.

Marker sentinel — both consts are `"\0"` (NUL, impossible in real keys):
- `KeyFolder::FOLDED_KEY_MARKER` — tags fold-produced keys so they emit UNQUOTED.
- `PathExpander::LITERAL_KEY_MARKER` — tags source-quoted dotted keys so they NEVER expand.
- Reference by const name, never the literal (see [[spec-invariants]]).

Strict conflicts:
- object-vs-primitive at a path, or a non-mergeable collision → `ToonStrictModeException`. Lenient mode overwrites.
- Deep merge only merges two assoc maps; anything else is a conflict.
