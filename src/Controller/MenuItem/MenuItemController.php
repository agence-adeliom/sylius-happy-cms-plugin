<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\MenuItem;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class MenuItemController
{
    private ?MenuItemRepositoryInterface $menuItemRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {
        $repository = $this->entityManager->getRepository(MenuItemInterface::class);
        if ($repository instanceof MenuItemRepositoryInterface) {
            $this->menuItemRepository = $repository;
        }
    }

    public function indexAction(Request $request): Response
    {
        return new RedirectResponse($this->router->generate($request->get('route')));
    }

    private function findMenuItemOr404(int $id): MenuItemInterface
    {
        $menuItem = null;
        if (null !== $this->menuItemRepository) {
            /** @var MenuItemInterface|null $menuItem */
            $menuItem = $this->menuItemRepository->find($id);

            if (null === $menuItem) {
                throw new NotFoundHttpException(sprintf('MenuItem with id %d does not exist.', $id));
            }
        }
        Assert::isInstanceOf($menuItem, MenuItemInterface::class);

        return $menuItem;
    }
}
