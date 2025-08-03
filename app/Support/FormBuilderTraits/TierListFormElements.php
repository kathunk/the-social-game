<?php

namespace App\Support\FormBuilderTraits;

use Illuminate\Support\Str;

trait TierListFOrmElements 
{
    public function tierListInputs(string $category) {
        $this->divider()
        ->subtitle($category);

        collect(['A', 'B', 'C', 'D', 'F'])->each(fn($tier) => 
            $this->input(
                property_name: Str::snake($category) . '-' . $tier,
                validation_rules: 'required',
                validation_messages: ['required' => 'Submisions are required'],
                placeholder: $tier . ' tier ' . Str::lower(Str::singular($category)),
            )
        );

        return $this;
    }
}
