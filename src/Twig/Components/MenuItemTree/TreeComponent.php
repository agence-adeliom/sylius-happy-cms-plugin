<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\MenuItemTree;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Menu\AllMenuItemsInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
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
        $id = $this->requestStack->getCurrentRequest()->get('menu_id') ?: '0';

        return $this->buildTree($this->allMenuItems->getArrayResult((int) $id));
    }

    #[LiveAction]
    public function moveUp(#[LiveArg] int $menuItemId): void
    {
        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        if ($menuItemToBeMoved->getPosition() > 0) {
            $targetItem = $this->menuItemRepository->findPreviousMenuItem($menuItemToBeMoved);

            $oldPosition = $menuItemToBeMoved->getPosition();
            $oldLft = $menuItemToBeMoved->getLft();
            $oldRgt = $menuItemToBeMoved->getRgt();

            if ($targetItem instanceof MenuItemInterface) {
                $targetItem->setPosition($oldPosition);
                $targetItem->setLft($oldLft);
                $targetItem->setRgt($oldRgt);
                $this->entityManager->persist($targetItem);

                $menuItemToBeMoved->setPosition($oldPosition - 1);
                $menuItemToBeMoved->setLft($oldLft - 2);
                $menuItemToBeMoved->setRgt($oldRgt - 2);
                $this->entityManager->persist($menuItemToBeMoved);
                $this->entityManager->flush();
            }
        }

        $this->requestStack->getCurrentRequest()->attributes->set('menu_id', $menuItemToBeMoved->getMenu()->getId());
    }

    #[LiveAction]
    public function moveDown(#[LiveArg] int $menuItemId): void
    {
        $menuItemToBeMoved = $this->menuItemRepository->find($menuItemId);

        $targetItem = $this->menuItemRepository->findNextMenuItem($menuItemToBeMoved);

        $oldPosition = $menuItemToBeMoved->getPosition();
        $oldLft = $menuItemToBeMoved->getLft();
        $oldRgt = $menuItemToBeMoved->getRgt();

        if ($targetItem instanceof MenuItemInterface) {
            $targetItem->setPosition($oldPosition);
            $targetItem->setLft($oldLft);
            $targetItem->setRgt($oldRgt);

            $menuItemToBeMoved->setPosition($oldPosition + 1);
            $menuItemToBeMoved->setLft($oldLft + 2);
            $menuItemToBeMoved->setRgt($oldRgt + 2);

            $this->entityManager->persist($menuItemToBeMoved);
            $this->entityManager->flush();
        }

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
