<?php

namespace App\Support\FormBuilderTraits\TierList;

trait TierListFormElements
{
    public function tierListGuess(array $answer_keys, string $type)
    {
        $header = collect($answer_keys)->values()->last();
        $shuffled = collect($answer_keys)->values()->slice(0, -1)->shuffle()->values()->toArray();

        $this->elements[] = [
            'type' => 'tier_list_guess',
            'property_name' => 'guesses_array',
            'answer_keys' => $shuffled,
            'round_type' => $type,
            'header' => $header,
        ];

        return $this;
    }
}
