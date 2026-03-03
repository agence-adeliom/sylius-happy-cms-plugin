<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\Block;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Sylius\Resource\Model\ResourceInterface;

class BlockCollection
{
    /** @var array<string, BlockTypeInterface> */
    protected array $blocks = [];

    /**
     * @param iterable<BlockTypeInterface> $blocksList
     */
    public function __construct(iterable $blocksList)
    {
        $blocks = [];
        foreach ($blocksList as $block) {
            $blocks[$block::class] = $block;
        }

        uasort($blocks, static fn ($a, $b) => $a->getPosition() <=> $b->getPosition());
        $this->blocks = $blocks;
    }

    public function enabledSupportFilter(): self
    {
        //if (null !== $this->entityDto) {
        //    $this->blocks = $this->blocks->filter(
        //        fn (BlockTypeInterface $block, $type) => $block->supports($this->entityDto->getFqcn(), $this->entityDto->getInstance())
        //    );
        //}

        return $this;
    }

    /**
     * @return array<BlockTypeInterface>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @param BlockTypeInterface[] $blockTypes
     *
     * @return BlockTypeInterface[]
     */
    public function getAllowedBlocks(?array $blockTypes, ?ResourceInterface $resource): array
    {
        $blocks = $this->getBlocks();

        if (empty($blockTypes)) {
            $blockTypes = $blocks ? array_keys($blocks) : [];
        }

        assert($resource instanceof ContentEditableInterface || null === $resource);

        return array_filter(
            $blocks,
            static fn (BlockTypeInterface $block, string $type) => in_array($type, $blockTypes) && $block->supports($resource),
            \ARRAY_FILTER_USE_BOTH,
        );
    }
}
