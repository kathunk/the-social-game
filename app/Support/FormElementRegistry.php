<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Auto-discovers per-game-mode form element providers, mirroring how
 * ChallengeRegistry and ModifierRegistry discover their classes: every
 * class under app/Support/FormBuilderElements/ implementing
 * FormElementProvider is scanned, and each of its public methods is mapped
 * by name. FormBuilder::__call resolves unknown methods here, so adding a
 * new game mode's custom UI never touches FormBuilder itself — just drop a
 * provider class in the folder.
 */
class FormElementRegistry
{
    protected static ?array $map = null;

    /**
     * @return array<string, class-string<FormElementProvider>> method name => provider class
     */
    public static function getAll(): array
    {
        if (self::$map) {
            return self::$map;
        }

        $path = app_path('Support/FormBuilderElements');

        if (! File::isDirectory($path)) {
            return self::$map = [];
        }

        $map = [];

        foreach (File::allFiles($path) as $file) {
            $relative = $file->getRelativePathname();
            $class = 'App\\Support\\FormBuilderElements\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

            if (! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, FormElementProvider::class)) {
                continue;
            }
            if ((new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isConstructor() || $method->class !== $class) {
                    continue;
                }

                $name = $method->getName();

                if (isset($map[$name])) {
                    throw new \Exception(
                        "Duplicate form element method [{$name}] found on [{$class}] and [{$map[$name]}]."
                    );
                }

                $map[$name] = $class;
            }
        }

        return self::$map = $map;
    }

    public static function resolve(string $method): ?FormElementProvider
    {
        $class = self::getAll()[$method] ?? null;

        return $class ? new $class : null;
    }
}
