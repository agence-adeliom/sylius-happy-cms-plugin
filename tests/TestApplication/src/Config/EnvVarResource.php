<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Config;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Marks the container as stale when an environment variable read at build time changes,
 * so that `make up` with a new value is enough (no cache:clear).
 */
final class EnvVarResource implements SelfCheckingResourceInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
    ) {
    }

    public static function read(string $name): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return \is_string($value) ? $value : '';
    }

    public function __toString(): string
    {
        return 'env.' . $this->name;
    }

    public function isFresh(int $timestamp): bool
    {
        return self::read($this->name) === $this->value;
    }
}
