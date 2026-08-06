<?php

namespace App\Support\FormBuilderElements\TierList;

use App\Support\FormBuilder;
use App\Support\FormElementProvider;

class TierListFormElements implements FormElementProvider
{
    public function tierListGuess(FormBuilder $form, array $answer_keys, string $type): void
    {
        $header = collect($answer_keys)->values()->last();
        $shuffled = collect($answer_keys)->values()->slice(0, -1)->shuffle()->values()->toArray();

        $form->addElement([
            'type' => 'tier_list_guess',
            'property_name' => 'guesses_array',
            'answer_keys' => $shuffled,
            'round_type' => $type,
            'header' => $header,
        ]);
    }
}
