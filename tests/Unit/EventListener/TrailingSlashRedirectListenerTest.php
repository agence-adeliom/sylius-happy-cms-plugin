<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\EventListener\TrailingSlashRedirectListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

final class TrailingSlashRedirectListenerTest extends TestCase
{
    public function testAPathWithoutItsFinalSlashIsRedirectedToTheCmsRoute(): void
    {
        $event = $this->notFound(Request::create('/about/team?utm=x'));

        $this->listener()->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/about/team/?utm=x', $response->getTargetUrl());
    }

    public function testNothingHappensWhenThePathWithASlashIsNotACmsRouteEither(): void
    {
        $event = $this->notFound(Request::create('/unknown'));

        $this->listener()->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testOnlyGetAndHeadRequestsAreRedirected(): void
    {
        $event = $this->notFound(Request::create('/about/team', 'POST'));

        $this->listener()->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testPathsEndingWithASlashAndOtherErrorsAreLeftAlone(): void
    {
        $withSlash = $this->notFound(Request::create('/about/'));
        $forbidden = new ExceptionEvent($this->createStub(HttpKernelInterface::class), Request::create('/about/team'), HttpKernelInterface::MAIN_REQUEST, new AccessDeniedHttpException());

        $this->listener()->onKernelException($withSlash);
        $this->listener()->onKernelException($forbidden);

        self::assertNull($withSlash->getResponse());
        self::assertNull($forbidden->getResponse());
    }

    private function listener(): TrailingSlashRedirectListener
    {
        $router = $this->createStub(RequestMatcherInterface::class);
        $router->method('matchRequest')->willReturnCallback(static function (Request $request): array {
            if ('/about/team/' !== $request->getPathInfo()) {
                throw new ResourceNotFoundException();
            }

            return ['_route' => 'cms_about_team'];
        });

        return new TrailingSlashRedirectListener($router);
    }

    private function notFound(Request $request): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException());
    }
}
