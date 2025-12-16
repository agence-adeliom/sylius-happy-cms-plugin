<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageController extends AbstractController
{
    public function __construct(
        protected RouteRenderService $routeRenderService,
        protected RouterInterface $router,
        protected TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        // Get locale from route parameter and set it in the request
        // This is necessary because the LocaleListener hasn't run yet
        $locale = $request->attributes->get('_locale', $request->getLocale());
        $request->setLocale($locale);

        /** @var ?PageRepositoryInterface $pageRepository */
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        if ($pageRepository instanceof PageRepositoryInterface) {
            $page = $pageRepository
                ->getHomePage($locale);
            $page->setCurrentLocale($locale);
            if (null !== $page) {
                $onlineRoute = $page->getOnlineRoute($locale);
                if ($onlineRoute instanceof RouteInterface) {
                    return $this->routeRenderService->renderAction(
                        $page,
                        $request,
                        $onlineRoute,
                    );
                }
            }
        }

        return new Response('Page not found', Response::HTTP_NOT_FOUND);
    }

    public function clearCacheAction(Request $request): Response
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');

        try {
            $this->routeRenderService->invalidCache();

            $flashBag->add('success', $this->translator->trans('sylius_happy_cms.cache.successfully_cleared'));
        } catch (\RuntimeException $exception) {
            try {
                $flashBag->add('error', $this->translator->trans('sylius_happy_cms.cache.something_went_wrong'));
            } catch (\RuntimeException $exception) {
                // DO nothing, flash service not available
            }
        }

        return new RedirectResponse($this->router->generate('sylius_happy_cms_admin_page_index'));
    }

    public function blockPreviewAction(Request $request): Response
    {
        $data = [];
        $blocks = [
            'block-demo-1' => array_merge([
                                              'position' => '1',
                                              'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\AccordionBlockType',
                                              'block_published' => '1',
                                          ], $data),
        ];

        return $this->render('@SyliusHappyCMSPlugin/front/blocks/preview.html.twig', [
            'blocks' => $blocks,
            'preview' => true,
            'data' => $request->get('data'),
        ]);
    }
}
