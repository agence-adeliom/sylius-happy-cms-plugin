<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures;

use Psr\Log\AbstractLogger;

/**
 * Collects the SQL run through Doctrine\DBAL\Logging\Middleware.
 */
final class QueryLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $queries = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (isset($context['sql']) && is_string($context['sql'])) {
            $this->queries[] = $context['sql'];
        }
    }

    /**
     * @return list<string>
     */
    public function selectsFrom(string $table): array
    {
        return array_values(array_filter(
            $this->queries,
            static fn (string $sql): bool => str_starts_with($sql, 'SELECT') && str_contains($sql, ' FROM ' . $table . ' '),
        ));
    }
}
