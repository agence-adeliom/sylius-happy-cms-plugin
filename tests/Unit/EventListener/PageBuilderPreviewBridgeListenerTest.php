<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\EventListener\PageBuilderPreviewBridgeListener;
use Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The preview bridge is injected in the pages rendered for the page builder, and nowhere else.
 */
final class PageBuilderPreviewBridgeListenerTest extends TestCase
{
    private const PAGE = '<html><body><main>Page</main></body></html>';

    private const PREVIEW_BLOCK_PAGE = '<html><body><div data-block-id="1" class="content-block-wrapper">Block</div></body></html>';

    public function testThePreviewGrantedByTheCmsRenderGetsTheBridge(): void
    {
        $request = $this->request('?happy_cms_preview=1');
        $request->attributes->set(PageBuilderPreviewBridgeListener::REQUEST_ATTRIBUTE, true);

        $content = $this->dispatch($request, new Response(self::PAGE));

        self::assertStringContainsString('<script src="/bundles/syliushappycmsplugin/page-builder-preview-bridge', $content);
        self::assertStringEndsWith('</script></body></html>', $content);
    }

    public function testAPreviewPageWithPreviewBlocksGetsTheBridge(): void
    {
        $content = $this->dispatch($this->request('?happy_cms_preview=1'), new Response(self::PREVIEW_BLOCK_PAGE));

        self::assertStringContainsString('page-builder-preview-bridge', $content);
    }

    public function testAPageOutsideThePreviewKeepsItsContent(): void
    {
        $request = $this->request('');
        $request->attributes->set(PageBuilderPreviewBridgeListener::REQUEST_ATTRIBUTE, true);

        self::assertSame(self::PREVIEW_BLOCK_PAGE, $this->dispatch($request, new Response(self::PREVIEW_BLOCK_PAGE)));
    }

    public function testAPreviewRequestWithoutGrantNorPreviewBlocksKeepsItsContent(): void
    {
        self::assertSame(self::PAGE, $this->dispatch($this->request('?happy_cms_preview=1'), new Response(self::PAGE)));
    }

    public function testANonHtmlPreviewResponseKeepsItsContent(): void
    {
        $response = new Response(self::PREVIEW_BLOCK_PAGE, 200, ['Content-Type' => 'application/json']);

        self::assertSame(self::PREVIEW_BLOCK_PAGE, $this->dispatch($this->request('?happy_cms_preview=1'), $response));
    }

    public function testASubRequestKeepsItsContent(): void
    {
        $content = $this->dispatch(
            $this->request('?happy_cms_preview=1'),
            new Response(self::PREVIEW_BLOCK_PAGE),
            HttpKernelInterface::SUB_REQUEST,
        );

        self::assertSame(self::PREVIEW_BLOCK_PAGE, $content);
    }

    private function request(string $query): Request
    {
        return Request::create('https://shop.example.com/page' . $query);
    }

    private function dispatch(Request $request, Response $response, int $type = HttpKernelInterface::MAIN_REQUEST): string
    {
        $listener = new PageBuilderPreviewBridgeListener(
            new PreviewRequestMatcher(),
            new Packages(null, [AssetHappyCMSPackage::PACKAGE_NAME => new AssetHappyCMSPackage()]),
        );
        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, $type, $response);
        $listener($event);

        return (string) $event->getResponse()->getContent();
    }
}
