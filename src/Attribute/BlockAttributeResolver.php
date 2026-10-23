<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Attribute;

/**
 * Reads #[AsBlock] / #[AsSharedBlock] on a block class, then on its parents (a block extending a concrete block
 * inherits its metadata). Results are cached per class: the attribute of a class never changes at runtime.
 *
 * @internal
 */
final class BlockAttributeResolver
{
    /** @var array<string, object|null> */
    private static array $cache = [];

    /**
     * @template T of AsBlock|AsSharedBlock
     *
     * @param class-string    $class
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     */
    public static function find(string $class, string $attributeClass): ?object
    {
        $key = $attributeClass . '@' . $class;
        if (!\array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::read($class, $attributeClass);
        }

        $attribute = self::$cache[$key];

        return $attribute instanceof $attributeClass ? $attribute : null;
    }

    /**
     * @template T of AsBlock|AsSharedBlock
     *
     * @param class-string    $class
     * @param class-string<T> $attributeClass
     *
     * @return T
     */
    public static function get(string $class, string $attributeClass, string $method): object
    {
        return self::find($class, $attributeClass) ?? throw new \LogicException(sprintf(
            'The block "%s" has no metadata for %s(): set #[%s] on the class or override %2$s().',
            $class,
            $method,
            (new \ReflectionClass($attributeClass))->getShortName(),
        ));
    }

    /**
     * @template T of AsBlock|AsSharedBlock
     *
     * @param class-string    $class
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     */
    private static function read(string $class, string $attributeClass): ?object
    {
        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            $attributes = $reflection->getAttributes($attributeClass);
            if ([] !== $attributes) {
                return $attributes[0]->newInstance();
            }
        }

        return null;
    }
}
