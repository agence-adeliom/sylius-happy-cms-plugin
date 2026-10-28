<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Menu;

use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuRootItemOnDeleteListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;

/**
 * `sylius_happy_cms.menu.root_item_on_delete_set_null: true`: the `root_item_id` key is `ON DELETE SET NULL`.
 *
 * @see MenuDeletionCascadeTest for the default mapping
 */
final class MenuDeletionTest extends MenuTestCase
{
    public function testTheRootItemKeyIsSetNullOnDelete(): void
    {
        $mapping = $this->entityManager->getClassMetadata(Menu::class)->getAssociationMapping('rootItem');

        self::assertSame('SET NULL', $mapping['joinColumns'][0]['onDelete']);
    }

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

    protected function configureEntityManager(EntityManagerInterface $entityManager): void
    {
        // What the extension registers when the option is true.
        $entityManager->getEventManager()->addEventListener([Events::loadClassMetadata], new MenuRootItemOnDeleteListener());
    }
}
