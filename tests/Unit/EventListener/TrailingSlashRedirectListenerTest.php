<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\EventListener\TrailingSlashRedirectListener;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
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

    public function testAPathOfAPublishedContentIsRedirected(): void
    {
        $event = $this->notFound(Request::create('/about/team'));

        $this->listener($this->content(online: true))->onKernelException($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame('/about/team/', $event->getResponse()->getTargetUrl());
    }

    public function testAPathOfAContentThatIsNotOnlineIsLeftToThe404(): void
    {
        // the CMS route exists, but RouteRenderService answers 404 for an unpublished content (or outside of its
        // publication dates): redirecting would answer 301 to a 404 and disclose the URL of the unpublished content
        $event = $this->notFound(Request::create('/about/team'));
        $preview = $this->notFound(Request::create('/about/team?happy_cms_preview=1'));

        $this->listener($this->content(online: false))->onKernelException($event);
        $this->listener($this->content(online: false))->onKernelException($preview);

        self::assertNull($event->getResponse());
        self::assertNull($preview->getResponse());
    }

    public function testTheContentIsLoadedFromItsIdWhenNoEnhancerAddedIt(): void
    {
        // a project whose dynamic router has no ContentRepositoryEnhancer: the match only holds "_content_id"
        $unpublished = $this->notFound(Request::create('/about/team'));
        $published = $this->notFound(Request::create('/about/team'));

        $this->listenerWithContentId($this->content(online: false))->onKernelException($unpublished);
        $this->listenerWithContentId($this->content(online: true))->onKernelException($published);

        self::assertNull($unpublished->getResponse());
        self::assertInstanceOf(RedirectResponse::class, $published->getResponse());
        self::assertSame('/about/team/', $published->getResponse()->getTargetUrl());
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

    private function listener(?CmsRoutableInterface $content = null): TrailingSlashRedirectListener
    {
        $router = $this->createStub(RequestMatcherInterface::class);
        $router->method('matchRequest')->willReturnCallback(static function (Request $request) use ($content): array {
            if ('/about/team/' !== $request->getPathInfo()) {
                throw new ResourceNotFoundException();
            }

            // the CMF dynamic router adds the content of the route (ContentRepositoryEnhancer)
            return ['_route' => 'cms_about_team'] + (null !== $content ? [RouteObjectInterface::CONTENT_OBJECT => $content] : []);
        });

        return new TrailingSlashRedirectListener($router);
    }

    private function listenerWithContentId(CmsRoutableInterface $content): TrailingSlashRedirectListener
    {
        $router = $this->createStub(RequestMatcherInterface::class);
        $router->method('matchRequest')->willReturn(['_route' => 'cms_about_team', RouteObjectInterface::CONTENT_ID => 'App\Entity\Page:12']);
        $contentRepository = $this->createMock(ContentRepositoryInterface::class);
        $contentRepository->expects(self::once())->method('findById')->with('App\Entity\Page:12')->willReturn($content);

        return new TrailingSlashRedirectListener($router, $contentRepository);
    }

    private function content(bool $online): CmsRoutableInterface
    {
        $content = $this->createStub(CmsRoutableInterface::class);
        $content->method('isOnline')->willReturn($online);

        return $content;
    }

    private function notFound(Request $request): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException());
    }
}
