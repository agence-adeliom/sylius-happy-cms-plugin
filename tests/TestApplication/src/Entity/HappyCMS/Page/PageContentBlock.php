<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlock;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__page_content_block')]
#[ORM\Index(columns: ['page_id', 'locale', 'position'], name: 'idx_page_locale_position')]
#[ORM\Index(columns: ['page_id', 'locale', 'published'], name: 'idx_page_locale_published')]
class PageContentBlock extends ContentBlock implements ContentBlockInterface
{
    #[ORM\ManyToOne(targetEntity: PageInterface::class)]
    #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    protected ?PageInterface $page = null;

    public function getPage(): ?PageInterface
    {
        return $this->page;
    }

    public function setPage(?PageInterface $page): void
    {
        $this->page = $page;
    }
}