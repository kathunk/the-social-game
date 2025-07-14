<?php

namespace App\Support;

// @todo this could honestly take a second look. Claude helped a lot. And it's not that pleasant to look at.
class FrontendComponentProcessor
{
    public static function propertiesForLivewire(array $frontendComponent, bool $useEmptyStringForSelects = false): array
    {
        $properties = [];

        if (! isset($frontendComponent['elements'])) {
            return $properties;
        }

        foreach ($frontendComponent['elements'] as $element) {
            if (isset($element['property_name'])) {
                if ($useEmptyStringForSelects &&
                    isset($element['type']) &&
                    $element['type'] === 'select') {
                    // null selects must be empty strings for LW
                    $properties[$element['property_name']] = '';
                } else {
                    $properties[$element['property_name']] = null;
                }
            }
        }

        return $properties;
    }

    public static function validationRulesForLivewire(array $frontendComponent): array
    {
        if (! isset($frontendComponent['elements'])) {
            return ['rules' => [], 'messages' => []];
        }

        return collect($frontendComponent['elements'])
            ->filter(
                fn ($element) => isset(
                    $element['property_name'],
                    $element['validation_rules']
                )
            )
            ->reduce(
                function ($carry, $element) {
                    $property = $element['property_name'];

                    $carry['rules'][$property] = $element['validation_rules'];

                    if (isset($element['validation_messages'])) {
                        $carry['messages'] = array_merge(
                            $carry['messages'] ?? [],
                            collect($element['validation_messages'])
                                ->mapWithKeys(
                                    fn ($message, $rule) => [
                                        "{$property}.{$rule}" => $message,
                                    ]
                                )
                                ->all()
                        );
                    }

                    return $carry;
                },
                ['rules' => [], 'messages' => []]
            );
    }
}
