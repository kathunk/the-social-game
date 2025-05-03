<?php

namespace App\Modifiers;

use App\Models\Modifier;
use App\Modifiers\Classes\BaseModifierClass;
use App\States\ModifierState;
use Illuminate\Support\Facades\File;

class ModifierRegistry
{
    protected static ?array $map = null;

    public static function getAll(): array
    {
        if (self::$map) {
            return self::$map;
        }

        $classes = collect(File::allFiles(app_path('Modifiers/Classes')))
            ->map(function ($file) {
                $relative = $file->getRelativePathname();
                $class = 'App\\Modifiers\\Classes\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

                if (! class_exists($class)) {
                    return null;
                }
                if (! is_subclass_of($class, BaseModifierClass::class)) {
                    return null;
                }
                if ((new \ReflectionClass($class))->isAbstract()) {
                    return null;
                }

                return new $class;
            })
            ->filter();

        // Check for duplicate keys before mapping
        $keys = $classes->map(fn ($instance) => $instance->key());
        $duplicates = $keys->duplicates();

        if ($duplicates->isNotEmpty()) {
            throw new \Exception('Duplicate modifier keys found: '.$duplicates->implode(', '));
        }

        $classes = $classes->mapWithKeys(function ($instance) {
            return [$instance->key() => $instance::class];
        });

        return self::$map = $classes->toArray();
    }

    public static function retrieveFromModel(string $key, Modifier $model): BaseModifierClass
    {
        $class = self::getAll()[$key] ?? throw new \Exception("Unknown modifier type: $key");

        return $class::fromModel($model);
    }

    public static function retrieveFromState(string $key, ModifierState $state): BaseModifierClass
    {
        $class = self::getAll()[$key] ?? throw new \Exception("Unknown modifier type: $key");

        return $class::fromState($state);
    }

    public static function retrieveFromKey(string $key): BaseModifierClass
    {
        $class = self::getAll()[$key] ?? throw new \Exception("Unknown modifier type: $key");

        return $class::fromKey($key);
    }

    public static function options(): array
    {
        return array_keys(self::getAll());
    }
}
