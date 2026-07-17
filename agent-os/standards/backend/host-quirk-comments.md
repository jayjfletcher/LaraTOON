# Document Host-Contract Quirks In-Class

When an integration works around a host framework's lifecycle/ordering quirk, the WHY + what-breaks-otherwise MUST be a code comment at the workaround. The comment is load-bearing — it stops the next reader from "cleaning up" the deliberate behavior.

Live examples:
- `Pao\Plugin` intentionally does NOT implement `Terminable` — Pest runs `terminate()` in registration order, which would detach Toon's filter *before* PAO writes its JSON. Filter is left attached; PHP cleans it up at shutdown.
- `ToonOutputFilter` targets the `STDOUT` constant because Symfony's ConsoleOutput writes there directly on Unix.

Rules:
- Any deliberate omission or unusual coupling to a host contract → comment the reason + the failure mode inline.
- Do NOT "fix" these: adding `Terminable`, detaching the filter, or tidying the STDOUT handling silently breaks interception.
