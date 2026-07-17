<?php

use Jayi\Toon\Overrides\Laravel\Ai\EncodesToonToolResults;
use Jayi\Toon\Overrides\Laravel\Ai\ToonToolResponse;
use Laravel\Ai\Tools\Request;

it('wraps array results in a ToonToolResponse', function () {
    $tool = new class
    {
        use EncodesToonToolResults;

        protected function run(Request $request): mixed
        {
            return ['id' => 1, 'name' => 'Ada'];
        }
    };

    $response = $tool->handle(new Request);

    expect($response)->toBeInstanceOf(ToonToolResponse::class);
    expect((string) $response)->toBe("id: 1\nname: Ada");
});

it('wraps object results in a ToonToolResponse', function () {
    $tool = new class
    {
        use EncodesToonToolResults;

        protected function run(Request $request): mixed
        {
            return (object) ['x' => 1];
        }
    };

    expect($tool->handle(new Request))->toBeInstanceOf(ToonToolResponse::class);
});

it('passes string results through unchanged', function () {
    $tool = new class
    {
        use EncodesToonToolResults;

        protected function run(Request $request): mixed
        {
            return 'plain result';
        }
    };

    expect($tool->handle(new Request))->toBe('plain result');
});

it('casts scalar results to string', function () {
    $tool = new class
    {
        use EncodesToonToolResults;

        protected function run(Request $request): mixed
        {
            return 42;
        }
    };

    expect($tool->handle(new Request))->toBe('42');
});
