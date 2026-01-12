<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageTranslationInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Page\PageRepository;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__page')]
class Page extends BasePage
{
    /** @var Collection<int, PageContentBlock> */
    #[ORM\OneToMany(targetEntity: PageContentBlock::class, mappedBy: 'contentOwner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $contentBlocks;

    /**
     * @return class-string<ContentBlockInterface>
     */
    public static function getContentBlockClass(): string
    {
        return PageContentBlock::class;
    }


    protected function createTranslation(): PageTranslationInterface
    {
        return new PageTranslation();
    }

    public static function getTranslationClass(): string
    {
        return PageTranslation::class;
    }
}
