<?php

use App\Support\FormBuilder;
use App\Support\FormBuilderElements\ElephantInTheRoom\ElephantFormElements;
use App\Support\FormBuilderElements\Farm\FarmFormElements;
use App\Support\FormBuilderElements\MorningRoutine\MorningRoutineFormElements;
use App\Support\FormBuilderElements\TierList\TierListFormElements;
use App\Support\FormElementRegistry;

it('auto-discovers every game mode\'s form element provider methods', function () {
    $map = FormElementRegistry::getAll();

    expect($map['tierListGuess'])->toBe(TierListFormElements::class);
    expect($map['farmMap'])->toBe(FarmFormElements::class);
    expect($map['farmActions'])->toBe(FarmFormElements::class);
    expect($map['morningRoutine'])->toBe(MorningRoutineFormElements::class);
    expect($map['morningRoutineResults'])->toBe(MorningRoutineFormElements::class);
    expect($map['elephantBoard'])->toBe(ElephantFormElements::class);
});

it('does not register protected provider helpers as element methods', function () {
    // FarmFormElements has protected sprite-config helpers that must stay internal
    expect(FormElementRegistry::getAll())->not->toHaveKey('buildSpaceSpriteConfig');
});

it('resolves provider methods through FormBuilder::__call with named arguments', function () {
    $form = (new FormBuilder)
        ->title('A round')
        ->tierListGuess(answer_keys: ['a', 'b', 'c', 'the header'], type: 'test-round');

    expect($form)->toBeInstanceOf(FormBuilder::class); // fluent chaining survives __call

    $built = $form->build();

    expect($built['elements'][0]['type'])->toBe('title');
    expect($built['elements'][1]['type'])->toBe('tier_list_guess');
    expect($built['elements'][1]['round_type'])->toBe('test-round');
    expect($built['elements'][1]['header'])->toBe('the header');
});

it('throws a BadMethodCallException for methods no provider defines', function () {
    (new FormBuilder)->definitelyNotARealElementMethod();
})->throws(BadMethodCallException::class);
