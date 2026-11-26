<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Marks preview responses private and not indexable. On a valid signed preview, it also lets the admin origin frame
 * the page and injects the preview agent that talks to the page builder, like the Symfony debug toolbar does.
 *
 * Registered with a low priority: it must run after the listeners that set X-Frame-Options or the Content-Type.
 */
final class PreviewResponseListener
{
    public const ASSET_PATH = 'page-builder-preview.js';

    public const ASSET_PACKAGE = 'sylius.happy.cms.assets.package';

    public function __construct(
        private readonly RequestMatcherInterface $previewRequestMatcher,
        private readonly SignedPreviewRequestValidatorInterface $validator,
        private readonly Packages $packages,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->previewRequestMatcher->matches($request) || !$response->isSuccessful() || !self::isHtml($request, $response)) {
            return;
        }

        $response->setPrivate();
        $response->headers->set('X-Robots-Tag', 'noindex');

        $context = $this->validator->validate($request);
        if (null === $context) {
            return;
        }

        $response->headers->remove('X-Frame-Options');
        self::allowFraming($response, $context->adminOrigin);
        $this->injectScript($response, $context->adminOrigin);
    }

    private static function isHtml(Request $request, Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        if (null !== $contentType) {
            return str_contains($contentType, 'html');
        }

        return 'html' === $request->getRequestFormat();
    }

    private static function allowFraming(Response $response, string $adminOrigin): void
    {
        $policy = trim((string) $response->headers->get('Content-Security-Policy'));

        if ('' === $policy) {
            $response->headers->set('Content-Security-Policy', 'frame-ancestors ' . $adminOrigin);

            return;
        }

        // A policy that already restricts the framing is the project's choice: it is left as is (documented).
        if (1 === preg_match('/(^|;)\s*frame-ancestors(\s|;|$)/i', $policy)) {
            return;
        }

        $response->headers->set('Content-Security-Policy', rtrim($policy, '; ') . '; frame-ancestors ' . $adminOrigin);
    }

    private function injectScript(Response $response, string $adminOrigin): void
    {
        $content = $response->getContent();
        if (false === $content) {
            return;
        }

        $position = strripos($content, '</body>');
        if (false === $position) {
            return;
        }

        $script = sprintf(
            '<script src="%s" data-admin-origin="%s" defer></script>',
            htmlspecialchars($this->packages->getUrl(self::ASSET_PATH, self::ASSET_PACKAGE), \ENT_QUOTES),
            htmlspecialchars($adminOrigin, \ENT_QUOTES),
        );

        $response->setContent(substr($content, 0, $position) . $script . substr($content, $position));
    }
}
