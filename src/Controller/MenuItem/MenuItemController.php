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
        $route = $request->get('route');
        assert(is_string($route), 'Route must be a string');
        return new RedirectResponse($this->router->generate($route));
    }
}
