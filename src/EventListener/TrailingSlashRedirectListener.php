<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * With "sylius_happy_cms.routing.trailing_slash", CMS routes end with "/". A request for the same path without
 * the final slash, which no route matches (404), is redirected (301) to the path with the slash when the CMS
 * router matches it. The query string is kept.
 */
final class TrailingSlashRedirectListener
{
    /**
     * Runs before the 404 listener of the plugin (redirect routes, priority 0).
     */
    public const PRIORITY = 64;

    public function __construct(
        private readonly RequestMatcherInterface $router,
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
            $this->router->matchRequest($probe);
        } catch (RoutingException) {
            return;
        }

        $queryString = $request->getQueryString();
        $event->setResponse(new RedirectResponse(
            $request->getBaseUrl() . $pathInfo . '/' . (null !== $queryString ? '?' . $queryString : ''),
            Response::HTTP_MOVED_PERMANENTLY,
        ));
    }
}
