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
}
