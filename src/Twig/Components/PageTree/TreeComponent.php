<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Components\PageTree;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Page\AllPagesInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class TreeComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;

    public function __construct(
        protected readonly AllPagesInterface $allPages,
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array<array-key, mixed> */
    public function getTree(): array
    {
        return $this->buildTree($this->allPages->getArrayResult());
    }

    #[LiveAction]
    public function moveUp(#[LiveArg] int $pageId): void
    {
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        $pageToBeMoved = $pageRepository->find($pageId);

        if (true !== $pageRepository->verify()) {
            $pageRepository->recoverFast([
                                             'sortByField' => 'lft', // Reorder sibling nodes by this field
                                             // during recovery
                                             'sortDirection' => 'ASC',
                                         ]);
            $this->entityManager->flush();
        }

        $pageRepository->moveUp($pageToBeMoved, 1);
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function moveTop(#[LiveArg] int $pageId): void
    {
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        $pageToBeMoved = $pageRepository->find($pageId);

        if (true !== $pageRepository->verify()) {
            $pageRepository->recoverFast([
                                             'sortByField' => 'lft', // Reorder sibling nodes by this field
                                             // during recovery
                                             'sortDirection' => 'ASC',
                                         ]);
            $this->entityManager->flush();
        }

        $pageRepository->moveUp($pageToBeMoved, true);
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function moveBottom(#[LiveArg] int $pageId): void
    {
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        $pageToBeMoved = $pageRepository->find($pageId);

        if (true !== $pageRepository->verify()) {
            $pageRepository->recoverFast([
                                             'sortByField' => 'lft',
                                             'sortDirection' => 'ASC',
                                         ]);
            $this->entityManager->flush();
        }

        $pageRepository->moveDown($pageToBeMoved, true);
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function moveDown(#[LiveArg] int $pageId): void
    {
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        $pageToBeMoved = $pageRepository->find($pageId);

        if (true !== $pageRepository->verify()) {
            $pageRepository->recoverFast([
                                             'sortByField' => 'lft', // Reorder sibling nodes by this field
                                             // during recovery
                                             'sortDirection' => 'ASC',
                                         ]);
            $this->entityManager->flush();
        }

        $pageRepository->moveDown($pageToBeMoved, 1);
        $this->entityManager->flush();
    }

    /**
     * @param array<array-key, mixed> $pages
     *
     * @return array<array-key, mixed>
     */
    private function buildTree(array $pages): array
    {
        $tree = [];
        $children = [];

        foreach ($pages as $page) {
            $treeChild = [
                'id' => $page['id'],
                'name' => $page['name'],
                'children' => $children[$page['id']] ?? [],
            ];

            if (null !== $page['parent_id']) {
                $children[$page['parent_id']][] = $treeChild;
            } else {
                $tree[] = $treeChild;
            }
        }

        return $tree;
    }
}
