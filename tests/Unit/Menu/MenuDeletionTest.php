<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Menu;

use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;

final class MenuDeletionTest extends MenuTestCase
{
    public function testAMenuIsDeletedWithItsItems(): void
    {
        $menu = $this->newMenu('main');
        $root = $this->newItem($menu, 'Root');
        $menu->setRootItem($root);
        $about = $this->newItem($menu, 'About', $root);
        $this->newItem($menu, 'Team', $about);
        $this->newItem($menu, 'Contact', $root);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $menu = $this->entityManager->find(Menu::class, $menu->getId());
        self::assertNotNull($menu);
        $this->entityManager->remove($menu);
        $this->entityManager->flush();

        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu'));
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item'));
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item_translation'));
    }

    public function testDeletingTheRootItemKeepsTheMenu(): void
    {
        $menu = $this->newMenu('main');
        $root = $this->newItem($menu, 'Root');
        $menu->setRootItem($root);
        $this->newItem($menu, 'About', $root);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        $menuId = $menu->getId();
        $this->entityManager->clear();

        $menu = $this->entityManager->find(Menu::class, $menuId);
        self::assertNotNull($menu);
        $root = $menu->getRootItem();
        self::assertNotNull($root);
        $this->entityManager->remove($root);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $menu = $this->entityManager->find(Menu::class, $menuId);
        self::assertNotNull($menu);
        self::assertNull($menu->getRootItem());
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item'));
    }
}
