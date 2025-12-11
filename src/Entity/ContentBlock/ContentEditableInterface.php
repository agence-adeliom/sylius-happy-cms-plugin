<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Doctrine\Common\Collections\Collection;

interface ContentEditableInterface
{
    /**
     * Get all content blocks for this entity.
     *
     * @return Collection<int, ContentBlockInterface>
     */
    public function getContentBlocks(): Collection;

    /**
     * Add a content block.
     */
    public function addContentBlock(ContentBlockInterface $contentBlock): void;

    /**
     * Remove a content block.
     */
    public function removeContentBlock(ContentBlockInterface $contentBlock): void;

    /**
     * Check if the entity has a specific content block.
     */
    public function hasContentBlock(ContentBlockInterface $contentBlock): bool;

    /**
     * Get published content blocks for a specific locale.
     *
     * @return Collection<int, ContentBlockInterface>
     */
    public function getPublishedContentBlocks(string $locale, ?string $layer = null): Collection;
}
