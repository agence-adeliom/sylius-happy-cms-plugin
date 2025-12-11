<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

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
            $contentBlock->setContentOwner($this);
        }
    }

    public function removeContentBlock(ContentBlockInterface $contentBlock): void
    {
        if ($this->contentBlocks->contains($contentBlock)) {
            $this->contentBlocks->removeElement($contentBlock);
            $contentBlock->setContentOwner(null);
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
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locale', $locale))
            ->andWhere(Criteria::expr()->eq('published', true))
            ->orderBy(['position' => 'ASC']);

        if (null !== $layer) {
            $criteria->andWhere(Criteria::expr()->eq('layer', $layer));
        }

        return $this->contentBlocks->matching($criteria);
    }
}
