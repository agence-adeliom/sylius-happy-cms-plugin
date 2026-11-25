<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Injects the page builder preview bridge (`page-builder-preview-bridge.js`) in a page rendered for the page builder.
 *
 * The page builder never reads the preview iframe: the preview may be served on another origin than the admin. The
 * bridge handles the blocks inside the preview and talks to the page builder through postMessage.
 *
 * A page is a page builder preview when the request asks for it (`?happy_cms_preview=1`) and either the CMS render
 * granted it (request attribute {@see self::REQUEST_ATTRIBUTE}) or the page contains preview blocks (content blocks
 * rendered in preview mode by a project controller).
 */
final class PageBuilderPreviewBridgeListener
{
    public const REQUEST_ATTRIBUTE = '_happy_cms_page_builder_preview';

    public const BRIDGE_ASSET = 'page-builder-preview-bridge.js';

    private const PREVIEW_BLOCK_MARKER = 'class="content-block-wrapper"';

    public function __construct(
        private readonly RequestMatcherInterface $previewRequestMatcher,
        private readonly Packages $packages,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->previewRequestMatcher->matches($request)) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        if (false === $content || !str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return;
        }

        if (true !== $request->attributes->get(self::REQUEST_ATTRIBUTE) && !str_contains($content, self::PREVIEW_BLOCK_MARKER)) {
            return;
        }

        $bodyEnd = strripos($content, '</body>');
        if (false === $bodyEnd) {
            return;
        }

        $script = sprintf(
            '<script src="%s"></script>',
            htmlspecialchars($this->packages->getUrl(self::BRIDGE_ASSET, AssetHappyCMSPackage::PACKAGE_NAME), \ENT_QUOTES),
        );
        $response->setContent(substr_replace($content, $script, $bodyEnd, 0));
    }
}
