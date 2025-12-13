<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait ContentEditableTrait
{
    /** @var Collection<int, ContentBlockInterface> */
    protected Collection $contentBlocks;

    private function initializeContentBlocksCollection(): void
    {
        $this->contentBlocks = new ArrayCollection();
    }

    /**
     * @return Collection<int, ContentBlockInterface>
     */
    public function getContentBlocks(): Collection
    {
        return $this->contentBlocks;
    }

    public function addContentBlock(ContentBlockInterface $contentBlock): void
    {
        if (!$this->contentBlocks->contains($contentBlock)) {
            $this->contentBlocks->add($contentBlock);
            if (method_exists($contentBlock, 'setContentOwner')) {
                $contentBlock->setContentOwner($this);
            }
        }
    }

    public function removeContentBlock(ContentBlockInterface $contentBlock): void
    {
        if ($this->contentBlocks->contains($contentBlock)) {
            $this->contentBlocks->removeElement($contentBlock);
            if (method_exists($contentBlock, 'setContentOwner')) {
                $contentBlock->setContentOwner(null);
            }
        }
    }

    public function hasContentBlock(ContentBlockInterface $contentBlock): bool
    {
        return $this->contentBlocks->contains($contentBlock);
    }

    /**
     * @return Collection<int, ContentBlockInterface>
     */
    public function getPublishedContentBlocks(string $locale, ?string $layer = null): Collection
    {
        // Filtrer via une closure puis trier par position asc
        $filtered = $this->contentBlocks->filter(function (ContentBlockInterface $contentBlock) use ($locale, $layer): bool {
            if (method_exists($contentBlock, 'getLocale') && $contentBlock->getLocale() !== $locale) {
                return false;
            }
            if (method_exists($contentBlock, 'isPublished') && !$contentBlock->isPublished()) {
                return false;
            }
            if (null !== $layer && method_exists($contentBlock, 'getLayer') && $contentBlock->getLayer() !== $layer) {
                return false;
            }

            return true;
        });

        $values = $filtered->getValues();
        usort($values, static function ($a, $b): int {
            $posA = method_exists($a, 'getPosition') ? $a->getPosition() : 0;
            $posB = method_exists($b, 'getPosition') ? $b->getPosition() : 0;

            return $posA <=> $posB;
        });

        return new ArrayCollection($values);
    }

    /**
     * Get content blocks for preview mode (includes unpublished blocks, sorted by preview position).
     *
     * @return Collection<int, ContentBlockInterface>
     */
    public function getPreviewContentBlocks(string $locale, ?string $layer = null): Collection
    {
        // Filter by locale and layer, include blocks based on preview publish state
        $filtered = $this->contentBlocks->filter(function (ContentBlockInterface $contentBlock) use ($locale, $layer): bool {
            if (method_exists($contentBlock, 'getLocale') && $contentBlock->getLocale() !== $locale) {
                return false;
            }

            // In preview mode, check preview publish state if available
            if (method_exists($contentBlock, 'isPreviewPublished')) {
                // Include blocks that are published in preview mode
                if (!$contentBlock->isPreviewPublished()) {
                    return false;
                }
            }

            if (null !== $layer && method_exists($contentBlock, 'getLayer') && $contentBlock->getLayer() !== $layer) {
                return false;
            }

            return true;
        });

        // Sort by preview position
        $values = $filtered->getValues();
        usort($values, static function ($a, $b): int {
            $posA = method_exists($a, 'getPreviewPosition') ? $a->getPreviewPosition() : 0;
            $posB = method_exists($b, 'getPreviewPosition') ? $b->getPreviewPosition() : 0;

            return $posA <=> $posB;
        });

        return new ArrayCollection($values);
    }
}
