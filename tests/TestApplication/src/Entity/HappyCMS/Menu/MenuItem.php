<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem as BaseMenuItem;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Menu\MenuItemRepository;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__menu_item')]
class MenuItem extends BaseMenuItem
{
    protected function createTranslation(): MenuItemTranslationInterface
    {
        return new MenuItemTranslation();
    }

    public static function getTranslationClass(): string
    {
        return MenuItemTranslation::class;
    }
}
