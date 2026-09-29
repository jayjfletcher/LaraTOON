<?php

use JayI\Toon\Overrides\Laravel\Ai\ToonMiddleware;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\StepResult;
use Laravel\Ai\PendingStep;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;

function toonPendingStep(?string $instructions): PendingStep
{
    return new PendingStep(
        number: 0,
        isFinalStep: false,
        provider: 'anthropic',
        model: 'claude',
        instructions: $instructions,
        messages: [],
        tools: [],
        schema: null,
        options: null,
    );
}

function runToonMiddleware(PendingStep $step): PendingStep
{
    $seen = null;

    (new ToonMiddleware)->handle($step, function (PendingStep $step) use (&$seen): StepResult {
        $seen = $step;

        return new StepResult(new StepResponse('', [], FinishReason::Stop, new TextUsage, new Meta('anthropic', 'claude')));
    });

    return $seen;
}

it('appends the toon note to the step instructions', function () {
    $step = runToonMiddleware(toonPendingStep('You are helpful.'));

    expect($step->instructions)
        ->toStartWith("You are helpful.\n\n")
        ->toContain('TOON (Token-Oriented Object Notation)');
});

it('uses the toon note alone when the agent has no instructions', function () {
    $step = runToonMiddleware(toonPendingStep(null));

    expect($step->instructions)->toStartWith('Tool results may be returned in TOON');
});

it('returns the next step result', function () {
    $result = new StepResult(new StepResponse('', [], FinishReason::Stop, new TextUsage, new Meta('anthropic', 'claude')));

    expect((new ToonMiddleware)->handle(toonPendingStep(null), fn () => $result))->toBe($result);
});
