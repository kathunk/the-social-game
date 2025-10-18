<?php

namespace App\Livewire\Concerns;

use Thunk\Verbs\Facades\Verbs;

trait HandlesClassActions
{
    public function callClassAction(
        string $action,
        string $type,
        string $class_key,
        ?array $component = null
    ) {
        $params = $this->round_properties[$class_key];
        $params = ['round_properties' => $params];

        $handler = match ($type) {
            'challenge' => $this->getChallengeHandler(),
            'modifier' => $this->getModifierHandler($class_key),
        };

        // If component not provided, get it from the component's stored data
        if ($component === null) {
            $component = $this->getComponentForType($type, $class_key);
        }

        if (! isset($component['elements'])) {
            return;
        }

        $all_elements = collect($component['elements'])->flatMap(function (
            $el
        ) {
            return [
                $el,
                ...collect($el['elements'] ?? [])->all(),
                ...collect($el['buttons'] ?? [])->all(),
            ];
        });

        $button = $all_elements->firstWhere(
            fn ($el) => ($el['type'] ?? null) === 'button' &&
                ($el['action'] ?? null) === $action
        );

        $fields = $button['properties_to_validate'] ?? [];

        if (! empty($fields)) {
            $validation = $this->validation_rules[$class_key] ?? [];

            $filtered_rules = [];
            $filtered_messages = [];
            foreach ($fields as $field) {
                if (isset($validation['rules'][$field])) {
                    $filtered_rules["round_properties.$class_key.$field"] =
                        $validation['rules'][$field];
                }
                if (isset($validation['messages'])) {
                    foreach ($validation['messages'] as $msg_key => $msg_val) {
                        if (str_starts_with($msg_key, "$field.")) {
                            $filtered_messages[
                                "round_properties.$class_key.$msg_key"
                            ] = $msg_val;
                        }
                    }
                }
            }

            $this->validate($filtered_rules, $filtered_messages);
        }

        // try {
            $response = $handler->{$action}(
                $this->player,
                $params['round_properties']
            );
        // } catch (\Exception $e) {
        //     $this->addError('action_error', $e->getMessage());

        //     return;
        // }

        Verbs::commit();

        $this->updateComponentsAfterAction($type, $class_key);

        return $response instanceof \Illuminate\Http\RedirectResponse ||
            $response instanceof \Livewire\Features\SupportRedirects\Redirector
            ? $response
            : null;
    }

    protected function getComponentForType(
        string $type,
        string $class_key
    ): array {
        return match ($type) {
            'challenge' => $this->challenge_component ?? [],
            'modifier' => $this->getModifierComponent($class_key),
        };
    }

    protected function getModifierComponent(string $class_key): array
    {
        // Handle collection of modifier components (GameDashboard)
        if (
            isset($this->modifier_components) &&
            isset($this->modifier_components[$class_key])
        ) {
            return $this->modifier_components[$class_key];
        }

        // Handle single modifier (SecretsPage) or fallback to fresh component
        return $this->getModifierHandler($class_key)->frontendComponent(
            $this->player
        );
    }

    protected function getChallengeHandler()
    {
        return $this->challenge_handler ?? $this->challengeHandler;
    }

    protected function getModifierHandler(string $class_key)
    {
        // Handle collection of modifiers (GameDashboard)
        if (isset($this->modifiers) && $this->modifiers) {
            return $this->modifiers
                ->firstWhere('class_key', $class_key)
                ->handler();
        }

        // Handle single modifier (SecretsPage)
        if (isset($this->modifier) && $this->modifier) {
            if ($this->modifier->class_key === $class_key) {
                return $this->modifier->handler();
            }
        }

        throw new \Exception("No modifier found for class_key: {$class_key}");
    }

    protected function updateComponentsAfterAction(
        string $type,
        string $class_key
    ): void {
        if ($type === 'challenge') {
            $this->challenge_component = $this->game->currentChallenge
                ?->fresh()
                ->handler()
                ->frontendComponent($this->player->fresh());
        }

        if ($type === 'modifier') {
            $modifier = $this->game
                ->fresh()
                ->modifiers()
                ->where('class_key', $class_key)
                ->first();

            if ($modifier) {
                $this->round_properties[$modifier->class_key] =
                    $modifier
                        ->handler()
                        ?->propertiesForLivewire($this->player->fresh()) ?? [];

                $this->validation_rules[$modifier->class_key] =
                    $modifier
                        ->handler()
                        ?->validationRulesForLivewire($this->player->fresh()) ??
                    [];

                if (isset($this->modifier_components)) {
                    $this->modifier_components[$modifier->class_key] = $modifier
                        ->fresh()
                        ->handler()
                        ->frontendComponent($this->player->fresh());
                }
            }
        }
    }
}
