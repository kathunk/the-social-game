<?php

namespace App\Challenges\Classes;

class PyramidScheme extends BaseChallengeClass
{
    CONST NAME = 'Pyramid Scheme';

    CONST DESCRIPTION = 'When a new player joins your team, gain 1 point. At the end of the challenge, the largest team will receive -20 points';

    public static function key(): string
    {
        return 'pyramid_scheme';
    }
}