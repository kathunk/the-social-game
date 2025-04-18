<?php

namespace App\Challenges;

use App\Challenges\Classes\BaseChallengeClass;
use App\Models\Challenge;
use App\States\ChallengeState;
use Illuminate\Support\Facades\File;

class ChallengeRegistry
{
    protected static ?array $map = null;

    public static function getAll(): array
    {
        if (self::$map) {
            return self::$map;
        }

        $classes = collect(File::allFiles(app_path('Challenges/Classes')))
            ->map(function ($file) {
                $relative = $file->getRelativePathname();
                $class = 'App\\Challenges\\Classes\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

                if (! class_exists($class)) {
                    return null;
                }
                if (! is_subclass_of($class, BaseChallengeClass::class)) {
                    return null;
                }
                if ((new \ReflectionClass($class))->isAbstract()) {
                    return null;
                }

                return new $class;
            })
            ->filter()
            ->mapWithKeys(function ($instance) {
                return [$instance->key() => $instance::class];
            });

        return self::$map = $classes->toArray();
    }

    public static function retrieveFromModel(string $key, Challenge $model): BaseChallengeClass
    {
        $class = self::getAll()[$key] ?? throw new \Exception("Unknown challenge type: $key");

        return $class::fromModel($model);
    }

    public static function retrieveFromState(string $key, ChallengeState $state): BaseChallengeClass
    {
        $class = self::getAll()[$key] ?? throw new \Exception("Unknown challenge type: $key");

        return $class::fromState($state);
    }

    public static function options(): array
    {
        return array_keys(self::getAll());
    }
}
