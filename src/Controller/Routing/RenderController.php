<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Routing;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenderController extends AbstractController
{
    public function __construct(
        protected readonly RouteRenderService $routeRenderService,
    ) {
    }

    public function renderAction(
        CmsRoutableInterface $contentDocument,
        Request $request,
    ): Response {
        // Get locale from route parameter and set it in the request
        // This is necessary because the LocaleListener hasn't run yet
        $locale = $request->attributes->get('_locale', $request->getLocale());
        $request->setLocale($locale);

        $contentDocument->setCurrentLocale($locale);

        return $this->routeRenderService->renderAction($contentDocument, $request);
    }
}
