# AI Integration

Toon provides middleware and traits for integrating with Laravel AI agents and MCP servers, automatically encoding tool results as TOON for reduced token usage.

## Agent Middleware

`ToonMiddleware` adds TOON format instructions to your agent's system prompt so the LLM knows how to interpret TOON-encoded tool results:

```php
use Jayi\Toon\Overrides\Laravel\Ai\ToonMiddleware;
use Laravel\Ai\Contracts\HasMiddleware;

class MyAgent implements HasMiddleware
{
    public function middleware(): array
    {
        return [new ToonMiddleware];
    }
}
```

When this middleware is active, the agent receives a concise explanation of TOON syntax prepended to its prompt, enabling it to parse TOON tool responses without additional configuration.

## Tool Trait

`EncodesToonToolResults` automatically TOON-encodes structured data returned from tool classes:

```php
use Jayi\Toon\Overrides\Laravel\Mcp\EncodesToonToolResults;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchUsers implements Tool
{
    use EncodesToonToolResults;

    protected function run(Request $request): mixed
    {
        return User::where('role', $request->role)->get()->toArray();
    }

    // handle() is provided by the trait — arrays/objects are TOON-encoded,
    // strings pass through unchanged
}
```

The trait provides a `handle()` method that calls your `run()` method and encodes the result:

- Arrays and objects are encoded via `Toon::smart()`
- Strings are returned as-is (assumed to be pre-formatted)

## ToonToolResponse

`ToonToolResponse` is a `Stringable` wrapper that lazily encodes data to TOON when cast to string:

```php
use Jayi\Toon\Responses\ToonToolResponse;

return new ToonToolResponse($data);
```

## MCP Response Macro

When `laravel/mcp` is installed, a `Response::toon()` macro is registered:

```php
use Laravel\Mcp\Response;

// In your MCP tool's handle method:
return Response::toon($data);

// Equivalent to:
// return Response::text(Toon::smart($data));
```

This is a drop-in replacement for `Response::json()` with automatic token savings.

## PAO Test Output

When [PAO](https://github.com/nunomaduro/pao) is installed, test output can be encoded as TOON instead of JSON. This is useful when AI agents are consuming test results.

Enable via environment variable:

```bash
TOON_PAO_OUTPUT=true php artisan test --compact
```

Or add to `.env`:

```
TOON_PAO_OUTPUT=true
```

The config key `toon.pao_output` is also available in the [publishable config](configuration.md).

This only activates when PAO detects an AI agent is running. Normal test runs are unaffected.
