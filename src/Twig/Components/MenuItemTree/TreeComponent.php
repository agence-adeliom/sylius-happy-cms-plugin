<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\MenuItemTree;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Menu\AllMenuItemsInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class TreeComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;

    public function __construct(
        protected readonly AllMenuItemsInterface $allMenuItems,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MenuItemRepositoryInterface $menuItemRepository,
        protected readonly RequestStack $requestStack,
    ) {
    }

    /** @return array<array-key, mixed> */
    public function getTree(): array
    {
        assert($this->requestStack->getCurrentRequest() instanceof Request, 'Current request must be instance of Request');

        /** @var string $id */
        $id = $this->requestStack->getCurrentRequest()->get('menu_id') ?: '0';

        return $this->buildTree($this->allMenuItems->getArrayResult((int) $id));
    }

    #[LiveAction]
    public function moveUp(#[LiveArg] int $menuItemId): void
    {
        assert($this->requestStack->getCurrentRequest() instanceof Request, 'Current request must be instance of Request');

        assert($this->menuItemRepository instanceof NestedTreeRepository && $this->menuItemRepository instanceof MenuItemRepositoryInterface, 'Page repository must be instance of NestedTreeRepository');

        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        assert($menuItemToBeMoved instanceof MenuItemInterface, 'Menu item to be moved must be instance of MenuItemInterface');

        assert($menuItemToBeMoved->getMenu() instanceof MenuInterface, 'Menu item is not linked to a menu');

        if (true !== $this->menuItemRepository->verify()) {
            $this->menuItemRepository->recoverFast([
                                                       'sortByField' => 'lft', // Reorder sibling nodes by this field
                                                       // during recovery
                                                       'sortDirection' => 'ASC',
                                                   ]);
            $this->entityManager->flush();
        }

        $this->menuItemRepository->moveUp($menuItemToBeMoved, 1);
        $this->entityManager->flush();

        $this->requestStack->getCurrentRequest()->attributes->set('menu_id', $menuItemToBeMoved->getMenu()->getId());
    }

    #[LiveAction]
    public function moveTop(#[LiveArg] int $menuItemId): void
    {
        assert($this->requestStack->getCurrentRequest() instanceof Request, 'Current request must be instance of Request');

        assert($this->menuItemRepository instanceof NestedTreeRepository && $this->menuItemRepository instanceof MenuItemRepositoryInterface, 'Page repository must be instance of NestedTreeRepository');

        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        assert($menuItemToBeMoved instanceof MenuItemInterface, 'Menu item to be moved must be instance of MenuItemInterface');

        assert($menuItemToBeMoved->getMenu() instanceof MenuInterface, 'Menu item is not linked to a menu');

        if (true !== $this->menuItemRepository->verify()) {
            $this->menuItemRepository->recoverFast([
                                                       'sortByField' => 'lft', // Reorder sibling nodes by this field
                                                       // during recovery
                                                       'sortDirection' => 'ASC',
                                                   ]);
            $this->entityManager->flush();
        }

        $this->menuItemRepository->moveUp($menuItemToBeMoved, true);
        $this->entityManager->flush();

        $this->requestStack->getCurrentRequest()->attributes->set('menu_id', $menuItemToBeMoved->getMenu()->getId());
    }

    #[LiveAction]
    public function moveBottom(#[LiveArg] int $menuItemId): void
    {
        assert($this->requestStack->getCurrentRequest() instanceof Request, 'Current request must be instance of Request');

        assert($this->menuItemRepository instanceof NestedTreeRepository && $this->menuItemRepository instanceof MenuItemRepositoryInterface, 'Page repository must be instance of NestedTreeRepository');

        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        assert($menuItemToBeMoved instanceof MenuItemInterface, 'Menu item to be moved must be instance of MenuItemInterface');

        assert($menuItemToBeMoved->getMenu() instanceof MenuInterface, 'Menu item is not linked to a menu');

        if (true !== $this->menuItemRepository->verify()) {
            $this->menuItemRepository->recoverFast([
                                                       'sortByField' => 'lft',
                                                       'sortDirection' => 'ASC',
                                                   ]);
            $this->entityManager->flush();
        }

        $this->menuItemRepository->moveDown($menuItemToBeMoved, true);
        $this->entityManager->flush();

        $this->requestStack->getCurrentRequest()->attributes->set('menu_id', $menuItemToBeMoved->getMenu()->getId());
    }

    #[LiveAction]
    public function moveDown(#[LiveArg] int $menuItemId): void
    {
        assert($this->requestStack->getCurrentRequest() instanceof Request, 'Current request must be instance of Request');

        assert($this->menuItemRepository instanceof NestedTreeRepository && $this->menuItemRepository instanceof MenuItemRepositoryInterface, 'Page repository must be instance of NestedTreeRepository');

        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        assert($menuItemToBeMoved instanceof MenuItemInterface, 'Menu item to be moved must be instance of MenuItemInterface');

        assert($menuItemToBeMoved->getMenu() instanceof MenuInterface, 'Menu item is not linked to a menu');

        if (true !== $this->menuItemRepository->verify()) {
            $this->menuItemRepository->recoverFast([
                                                       'sortByField' => 'lft', // Reorder sibling nodes by this field
                                                       // during recovery
                                                       'sortDirection' => 'ASC',
                                                   ]);
            $this->entityManager->flush();
        }

        $this->menuItemRepository->moveDown($menuItemToBeMoved, 1);
        $this->entityManager->flush();

        $this->requestStack->getCurrentRequest()->attributes->set('menu_id', $menuItemToBeMoved->getMenu()->getId());
    }

    /**
     * @param array<array-key, mixed> $menuItems
     *
     * @return array<array-key, mixed>
     */
    private function buildTree(array $menuItems): array
    {
        $tree = [];
        $children = [];

        foreach ($menuItems as $menuItem) {
            /**
             * @var array{
             *     'id': int,
             *     'name': string,
             *     'parent_id': int|null,
             * } $menuItem
             */
            $treeChild = [
                'id' => $menuItem['id'],
                'name' => $menuItem['name'],
                'children' => $children[$menuItem['id']] ?? [],
            ];

            if (null !== $menuItem['parent_id']) {
                $children[$menuItem['parent_id']][] = $treeChild;
            } else {
                $tree[] = $treeChild;
            }
        }

        return $tree;
    }
}
