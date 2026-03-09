<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Sylius\Resource\Model\ResourceInterface;

class SharedBlockCollection
{
    /** @var array<string, SharedBlockTypeInterface> */
    protected array $blocks;

    /**
     * @param iterable<SharedBlockTypeInterface> $blocksList
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

    public function enabledSupportFilter(?ResourceInterface $resource = null): self
    {
        $this->filterSupportedBlocks($resource);

        return $this;
    }

    /**
     * @return SharedBlockTypeInterface[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return array<SharedBlockTypeInterface>
     */
    public function getAllowedBlocks(?ResourceInterface $resource = null): array
    {
        $blocks = $this->getBlocks();

        if($resource instanceof ContentEditableInterface) {
            return array_filter(
                $blocks,
                static fn (SharedBlockTypeInterface $block, string $type) => $block->supports($resource),
                \ARRAY_FILTER_USE_BOTH,
            );
        }

        return $blocks;
    }

    private function filterSupportedBlocks(?ResourceInterface $resource = null): void
    {
        $this->blocks = $this->getAllowedBlocks($resource);
    }
}
