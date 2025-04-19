<?php

namespace App\Challenges\Classes;

class PressTheButton extends BaseChallengeClass
{
    const NAME = 'Press The Button';

    const DESCRIPTION = 'Once any player on your team presses the button, you will only have 60 seconds to press it. After that, the button will lock. Your team will receive points equal to: (% of teammates who pressed the button - 50%) * 100';

    public static function key(): string
    {
        return 'press_the_button';
    }

    public function frontendComponent(): array
    {
        // if ($this->state->is_locked) {
        //     return $this->form()
        //         ->title('You pressed the button!')->subtitle('You can no longer press the button.')
        //         ->table([
        //             'Teammates who pressed the button' => $this->state->team->players->pluck('name')->implode(', '),
        //         ])
        //         ->build();
        // }

        // return $this->form()
        //     ->button('Press the button')->action('pressButton')
        //     ->build();
    }

    public function validate()
    {
        // validate they are allowed to press the button, based on the challenge state
    }

    public function pressButton()
    {
        // fire an event to record the button press
    }
}
