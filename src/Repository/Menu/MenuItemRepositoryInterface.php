<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Menu;

use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\RepositoryInterface as GedmoRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends GedmoRepositoryInterface<MenuItemInterface>
 * @extends RepositoryInterface<MenuItemInterface>
 */
interface MenuItemRepositoryInterface extends
    RepositoryInterface,
    TranslationRepositoryInterface,
    GedmoRepositoryInterface
{
    /**
     * @return array<MenuItemInterface>|QueryBuilder ($returnQueryBuilder ? QueryBuilder : MenuItem[])
     */
    public function getByMenu(Menu $menu, bool $returnQueryBuilder = false): array|QueryBuilder;

    public function filterByMenu(int $menuId, string $locale): QueryBuilder;

    public function findPreviousMenuItem(MenuItemInterface $menuItem): ?MenuItemInterface;

    public function findNextMenuItem(MenuItemInterface $menuItem): ?MenuItemInterface;

    /**
     * @param array<string, mixed> $cacheConfig
     */
    public function setConfig(array $cacheConfig): void;
}
