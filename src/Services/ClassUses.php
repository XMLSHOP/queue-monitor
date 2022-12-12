<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

class ClassUses
{
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     */
    public static function classUsesRecursive(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        /** @phpstan-ignore-next-line */
        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += self::traitUsesRecursive($class);
        }

        return array_unique($results);
    }

    /**
     * Returns all traits used by a trait and its traits.
     */
    public static function traitUsesRecursive(string $trait): array
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += self::traitUsesRecursive($trait);
        }

        return $traits;
    }
}
