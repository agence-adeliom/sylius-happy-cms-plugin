<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlock;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class PageContentBlock extends ContentBlock implements PageContentBlockInterface
{
    #[ORM\ManyToOne(targetEntity: PageInterface::class, inversedBy: 'contentBlocks')]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', nullable: false, onDelete: 'set null')]
    protected ?PageInterface $contentOwner = null;

    public function getContentOwner(): ?PageInterface
    {
        return $this->contentOwner;
    }

    public function setContentOwner(?PageInterface $contentOwner): void
    {
        $this->contentOwner = $contentOwner;
    }

    /**
     * @deprecated Use getContentOwner() instead
     */
    public function getPage(): ?PageInterface
    {
        return $this->getContentOwner();
    }

    /**
     * @deprecated Use setContentOwner() instead
     */
    public function setPage(?PageInterface $page): void
    {
        $this->setContentOwner($page);
    }
}
