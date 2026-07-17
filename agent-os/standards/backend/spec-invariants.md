# Spec-Traceable Invariants

TOON output must match the spec exactly. Protect deliberate behavior from well-meaning "fixes".

Rules:
- Cite the spec section + reason on any non-obvious rule, in the doc comment.
  - e.g. quoting decisions → §7.2; escapes → §7.1; canonical number form → §2.
- Known hard invariants — don't "simplify":
  - Only 5 escape sequences: `\\ \" \n \r \t` (other controls → `\uXXXX`).
  - Numbers: canonical decimal, no scientific/trailing/leading zeros (except spec exponent range).
- Internal sentinels are named class constants, referenced by name on BOTH produce + consume sides — never inline the literal:
  - `KeyFolder::FOLDED_KEY_MARKER`, `PathExpander::LITERAL_KEY_MARKER`.

```php
/** ... quoting per TOON spec §7.2, escaping per §7.1. Only five escapes valid. */
```
