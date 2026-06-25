<?php

namespace Jayi\Toon\Overrides\Laravel\Ai;

use Closure;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Laravel AI agent middleware that appends TOON format instructions to the prompt.
 *
 * Add to your agent's `middleware()` array so the LLM knows how to interpret
 * TOON-encoded tool results.
 */
class ToonMiddleware
{
    private const string INSTRUCTION = 'Tool results may be returned in TOON (Token-Oriented Object Notation) format instead of JSON. '
        .'TOON is a line-oriented, indentation-based format: '
        .'Objects use `key: value` pairs with indentation for nesting. '
        .'Arrays declare length and fields in headers: `key[N]{field1,field2}:` followed by rows. '
        .'Inline primitive arrays: `key[N]: v1,v2,v3`. '
        .'Dotted keys represent nested paths: `a.b.c: 1` means `{"a":{"b":{"c":1}}}`. '
        .'Parse TOON tool results the same way you would parse JSON tool results.';

    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        return $next($prompt->append(self::INSTRUCTION));
    }
}
