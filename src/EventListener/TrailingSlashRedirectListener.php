<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * With "sylius_happy_cms.routing.trailing_slash", CMS routes end with "/". A request for the same path without
 * the final slash, which no route matches (404), is redirected (301) to the path with the slash when the CMS
 * router matches it and its content is online (CmsRoutableInterface::isOnline(), the check of RouteRenderService):
 * the route of an unpublished content, or of a content outside of its publication dates, is kept in the database
 * but answers 404, so the path without the slash answers 404 too. The content is the one added to the match by the
 * dynamic router (ContentRepositoryEnhancer), or else loaded from the "_content_id" of the route. The query string is
 * kept.
 */
final class TrailingSlashRedirectListener
{
    /**
     * Runs before the 404 listener of the plugin (redirect routes, priority 0).
     */
    public const PRIORITY = 64;

    public function __construct(
        private readonly RequestMatcherInterface $router,
        private readonly ?ContentRepositoryInterface $contentRepository = null,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        if (!$request->isMethodSafe() || str_ends_with($pathInfo, '/')) {
            return;
        }

        $probe = $request->duplicate();
        $probe->server->set('REQUEST_URI', $request->getBaseUrl() . $pathInfo . '/');

        try {
            $parameters = $this->router->matchRequest($probe);
        } catch (RoutingException) {
            return;
        }

        // same rule as RouteRenderService: a content that is not online answers 404 (preview included: the preview
        // URL is the route itself, with the slash)
        $content = $parameters[RouteObjectInterface::CONTENT_OBJECT] ?? null;
        $contentId = $parameters[RouteObjectInterface::CONTENT_ID] ?? null;
        if (null === $content && is_string($contentId) && null !== $this->contentRepository) {
            // dynamic router without ContentRepositoryEnhancer: the match only holds the content id
            $content = $this->contentRepository->findById($contentId);
        }
        if ($content instanceof CmsRoutableInterface && !$content->isOnline()) {
            return;
        }

        $queryString = $request->getQueryString();
        $event->setResponse(new RedirectResponse(
            $request->getBaseUrl() . $pathInfo . '/' . (null !== $queryString ? '?' . $queryString : ''),
            Response::HTTP_MOVED_PERMANENTLY,
        ));
    }
}
