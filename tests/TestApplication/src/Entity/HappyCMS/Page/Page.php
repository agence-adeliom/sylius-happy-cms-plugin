<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageTranslationInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Page\PageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__page')]
class Page extends BasePage
{
    protected function createTranslation(): PageTranslationInterface
    {
        return new PageTranslation();
    }

    public static function getTranslationClass(): string
    {
        return PageTranslation::class;
    }
}
