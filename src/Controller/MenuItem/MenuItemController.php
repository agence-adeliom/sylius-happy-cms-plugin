<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\MenuItem;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

class MenuItemController
{
    /** @var ObjectRepository<MenuItemRepositoryInterface> */
    private ObjectRepository $menuItemRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private RouterInterface $router,
    ) {
        $this->menuItemRepository = $this->entityManager->getRepository(MenuItemInterface::class);
    }

    public function indexAction(Request $request): Response
    {
        return new RedirectResponse($this->router->generate($request->get('route')));
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function treeAction(Request $request): Response
    {
        $menuId = (int) $request->get('menu');
        $menuItems = $this->menuItemRepository->filterByMenu($menuId, 'fr_FR')->getQuery()->setMaxResults(1)->getResult();

        return new Response(
            $this->twig->render(
                '@SyliusHappyCMSPlugin/menu_item/_treeWithButtons.html.twig',
                [
                    'menu_items' => $menuItems,
                    'menu' => $menuId,
                ],
            ),
        );
    }

    public function moveUpAction(int $id): Response
    {
        $menuItemToBeMoved = $this->findMenuItemOr404($id);

        if ($menuItemToBeMoved->getPosition() > 0) {
            $otherMenuItemToBeMoved = $this->menuItemRepository->findPreviousMenuItem($menuItemToBeMoved);
            if ($otherMenuItemToBeMoved) {
                $oldPosition = $menuItemToBeMoved->getPosition();

                $menuItemToBeMoved->setPosition($menuItemToBeMoved->getPosition() - 1);
                $otherMenuItemToBeMoved->setPosition($oldPosition);

                $this->entityManager->flush();
            }
        }

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    public function moveDownAction(int $id): Response
    {
        $menuItemToBeMoved = $this->findMenuItemOr404($id);

        $otherMenuItemToBeMoved = $this->menuItemRepository->findNextMenuItem($menuItemToBeMoved);
        if ($otherMenuItemToBeMoved) {
            $oldPosition = $menuItemToBeMoved->getPosition();

            $menuItemToBeMoved->setPosition($menuItemToBeMoved->getPosition() + 1);
            $otherMenuItemToBeMoved->setPosition($oldPosition);

            $this->entityManager->flush();
        }

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    private function findMenuItemOr404(int $id): MenuItemInterface
    {
        /** @var MenuItemInterface|null $menuItem */
        $menuItem = $this->menuItemRepository->find($id);

        if (null === $menuItem) {
            throw new NotFoundHttpException(sprintf('MenuItem with id %d does not exist.', $id));
        }

        Assert::isInstanceOf($menuItem, MenuItem::class);

        return $menuItem;
    }
}
