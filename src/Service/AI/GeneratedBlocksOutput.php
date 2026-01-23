<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Service\AI;

/**
 * Represents the output of AI-generated blocks
 */
final readonly class GeneratedBlocksOutput
{
    /**
     * @param array<int, array{
     *     block_type: string,
     *     position: int,
     *     block_published: bool,
     *     data: array<string, mixed>
     * }> $blocks
     */
    public function __construct(
        public array $blocks,
    ) {
    }

    /**
     * Get the list of generated blocks
     *
     * @return array<int, array{
     *     block_type: string,
     *     position: int,
     *     block_published: bool,
     *     data: array<string, mixed>
     * }>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get the number of generated blocks
     */
    public function getCount(): int
    {
        return count($this->blocks);
    }
}
