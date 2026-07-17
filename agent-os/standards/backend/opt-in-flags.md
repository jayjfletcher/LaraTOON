# Opt-In Flags: Config-First, Env Fallback

Optional/opt-in behavior resolves in this order:

1. Laravel config key (`toon.pao_output`) — when container + `config` are bound
2. Env var fallback (`TOON_PAO_OUTPUT`) — when no container (CLI/tooling context)
3. Always coerce with `filter_var(..., FILTER_VALIDATE_BOOLEAN)` — env/config strings like `"true"` aren't real bools

```php
if (class_exists(Container::class)) {
    $c = Container::getInstance();
    if ($c->bound('config')) {
        return filter_var($c->make('config')->get('toon.pao_output', $this->envFlag()), FILTER_VALIDATE_BOOLEAN);
    }
}
return $this->envFlag();
```

Rules:
- Read defensively — never assume the container is booted.
- New opt-in flag → config key with env default, boolean-coerced. Same defensive read as [[framework-optional]].
