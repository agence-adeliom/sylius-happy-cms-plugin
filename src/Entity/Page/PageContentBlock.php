<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlock;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;

#[ORM\MappedSuperclass]
class PageContentBlock extends ContentBlock implements PageContentBlockInterface
{
    #[ORM\ManyToOne(targetEntity: PageInterface::class, inversedBy: 'contentBlocks')]
    #[ORM\JoinColumn(name: 'content_owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'set null')]
    protected ?ResourceInterface $contentOwner = null;

    public function getContentOwner(): ?ResourceInterface
    {
        return $this->contentOwner;
    }

    public function setContentOwner(?ResourceInterface $contentOwner): void
    {
        $this->contentOwner = $contentOwner;
    }
}
