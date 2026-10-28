<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Menu;

use Doctrine\ORM\Internal\TopologicalSort\CycleDetectedException;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;

/**
 * Default mapping (`sylius_happy_cms.menu.root_item_on_delete_set_null` not set or false): the `root_item_id` key is
 * `ON DELETE CASCADE`, as before the option.
 *
 * @see MenuDeletionTest for the `SET NULL` mapping
 */
final class MenuDeletionCascadeTest extends MenuTestCase
{
    public function testTheRootItemKeyCascadesOnDeleteByDefault(): void
    {
        $mapping = $this->entityManager->getClassMetadata(Menu::class)->getAssociationMapping('rootItem');

        self::assertSame('CASCADE', $mapping['joinColumns'][0]['onDelete']);
    }

    /**
     * The menu references its root item (cascading key) and the root item references the menu: under doctrine/orm 3
     * the unit of work cannot order the deletions.
     */
    public function testAMenuWithItsRootItemCannotBeDeletedUnderOrm3(): void
    {
        if (!class_exists(CycleDetectedException::class)) {
            self::markTestSkipped('doctrine/orm 3 is required.');
        }

        $menuId = $this->saveMenuWithItems();

        $menu = $this->entityManager->find(Menu::class, $menuId);
        self::assertNotNull($menu);
        $this->entityManager->remove($menu);

        $this->expectException(CycleDetectedException::class);
        $this->entityManager->flush();
    }

    /**
     * Workaround with the default mapping: detach the root item from the menu first, in its own flush.
     */
    public function testAMenuIsDeletedOnceItsRootItemIsUnset(): void
    {
        $menuId = $this->saveMenuWithItems();

        $menu = $this->entityManager->find(Menu::class, $menuId);
        self::assertNotNull($menu);
        $menu->setRootItem(null);
        $this->entityManager->flush();
        $this->entityManager->remove($menu);
        $this->entityManager->flush();

        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu'));
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item'));
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu_item_translation'));
    }

    /**
     * The database cascades the deletion of the root item to the menu row.
     */
    public function testDeletingTheRootItemDeletesTheMenu(): void
    {
        $menuId = $this->saveMenuWithItems();

        $menu = $this->entityManager->find(Menu::class, $menuId);
        self::assertNotNull($menu);
        $root = $menu->getRootItem();
        self::assertNotNull($root);
        $this->entityManager->remove($root);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertNull($this->entityManager->find(Menu::class, $menuId));
        self::assertSame(0, $this->rowCount('sylius_happy_cms__menu'));
    }

    private function saveMenuWithItems(): int
    {
        $menu = $this->newMenu('main');
        $root = $this->newItem($menu, 'Root');
        $menu->setRootItem($root);
        $about = $this->newItem($menu, 'About', $root);
        $this->newItem($menu, 'Team', $about);
        $this->newItem($menu, 'Contact', $root);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        $menuId = $menu->getId();
        self::assertNotNull($menuId);
        $this->entityManager->clear();

        return $menuId;
    }
}
