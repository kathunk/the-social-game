<?php

namespace App\Rules;

abstract class StringableRule
{
    abstract public static function keyword(): string;

    abstract public static function validate(string $attribute, $value, array $parameters, $validator): bool;

    public static function register(): void
    {
        \Illuminate\Support\Facades\Validator::extend(
            static::keyword(),
            [static::class, 'validate']
        );
    }
}
