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
        return $this->routeRenderService->renderAction($contentDocument, $request);
    }
}
