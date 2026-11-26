<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\EventListener\PreviewResponseListener;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewContext;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PreviewResponseListenerTest extends TestCase
{
    private const string ADMIN = 'http://admin.localhost:8099';

    private const string HTML = '<html><body><p>Page</p></body></html>';

    public function testItInjectsTheScriptAndFrameHeadersOnASignedPreview(): void
    {
        $response = $this->handle(new Response(self::HTML));

        self::assertSame(
            '<html><body><p>Page</p><script src="/bundles/happy/page-builder-preview.js" data-admin-origin="http://admin.localhost:8099" defer></script></body></html>',
            $response->getContent(),
        );
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        self::assertSame('noindex', $response->headers->get('X-Robots-Tag'));
        self::assertSame('frame-ancestors http://admin.localhost:8099', $response->headers->get('Content-Security-Policy'));
    }

    public function testItEscapesTheAdminOrigin(): void
    {
        $response = $this->handle(new Response(self::HTML), context: new SignedPreviewContext('sylius_happy_cms.page', 42, 'fr_FR', 'http://a"b.test'));

        self::assertStringContainsString('data-admin-origin="http://a&quot;b.test"', (string) $response->getContent());
    }

    public function testItRemovesXFrameOptions(): void
    {
        $response = new Response(self::HTML, 200, ['X-Frame-Options' => 'sameorigin']);

        self::assertFalse($this->handle($response)->headers->has('X-Frame-Options'));
    }

    public function testItAppendsFrameAncestorsToAnExistingCsp(): void
    {
        $response = new Response(self::HTML, 200, ['Content-Security-Policy' => "default-src 'self'"]);

        self::assertSame("default-src 'self'; frame-ancestors http://admin.localhost:8099", $this->handle($response)->headers->get('Content-Security-Policy'));
    }

    public function testItKeepsAnExistingFrameAncestors(): void
    {
        $response = new Response(self::HTML, 200, ['Content-Security-Policy' => "frame-ancestors 'self'"]);

        self::assertSame("frame-ancestors 'self'", $this->handle($response)->headers->get('Content-Security-Policy'));
    }

    public function testItInsertsBeforeTheLastClosingBody(): void
    {
        $html = '<html><body><script>var s = "</body>";</script></BODY></html>';

        $content = (string) $this->handle(new Response($html))->getContent();

        self::assertStringStartsWith('<html><body><script>var s = "</body>";</script><script src=', $content);
        self::assertStringEndsWith('</script></BODY></html>', $content);
    }

    public function testItLeavesHtmlWithoutClosingBodyUnchanged(): void
    {
        $response = $this->handle(new Response('<p>fragment</p>'));

        self::assertSame('<p>fragment</p>', $response->getContent());
        self::assertSame('noindex', $response->headers->get('X-Robots-Tag'));
    }

    public function testItDoesNothingOnARedirect(): void
    {
        $response = $this->handle(new Response('', 302, ['Location' => '/autre-page', 'X-Frame-Options' => 'sameorigin']));

        self::assertSame('sameorigin', $response->headers->get('X-Frame-Options'));
        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertFalse($response->headers->has('X-Robots-Tag'));
    }

    /**
     * @return iterable<string, array{Response}>
     */
    public static function untouchedResponses(): iterable
    {
        yield 'json' => [new Response('{"a":"</body>"}', 200, ['Content-Type' => 'application/json'])];
        yield '403' => [new Response(self::HTML, 403)];
        yield '500' => [new Response(self::HTML, 500)];
    }

    #[DataProvider('untouchedResponses')]
    public function testItDoesNothingOnJsonOrErrors(Response $response): void
    {
        $content = $response->getContent();

        $response = $this->handle($response);

        self::assertSame($content, $response->getContent());
        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function testItOnlySetsPrivateAndNoindexOnAnUnsignedPreview(): void
    {
        $response = $this->handle(new Response(self::HTML, 200, ['X-Frame-Options' => 'sameorigin']), context: null);

        self::assertSame(self::HTML, $response->getContent());
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        self::assertSame('noindex', $response->headers->get('X-Robots-Tag'));
        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertSame('sameorigin', $response->headers->get('X-Frame-Options'));
    }

    public function testItIgnoresARequestThatIsNotAPreview(): void
    {
        $response = $this->handle(new Response(self::HTML), uri: '/ma-page');

        self::assertSame(self::HTML, $response->getContent());
        self::assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function testItIgnoresSubRequests(): void
    {
        $response = $this->handle(new Response(self::HTML), type: HttpKernelInterface::SUB_REQUEST);

        self::assertSame(self::HTML, $response->getContent());
        self::assertFalse($response->headers->has('X-Robots-Tag'));
    }

    private function handle(
        Response $response,
        ?SignedPreviewContext $context = new SignedPreviewContext('sylius_happy_cms.page', 42, 'fr_FR', self::ADMIN),
        string $uri = '/ma-page?happy_cms_preview=1',
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): Response {
        $validator = $this->createStub(SignedPreviewRequestValidatorInterface::class);
        $validator->method('validate')->willReturn($context);

        $packages = new Packages(null, ['sylius.happy.cms.assets.package' => new PathPackage('/bundles/happy', new EmptyVersionStrategy())]);

        $listener = new PreviewResponseListener(new PreviewRequestMatcher(), $validator, $packages);
        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), Request::create($uri), $type, $response);

        $listener->onKernelResponse($event);

        return $event->getResponse();
    }
}
