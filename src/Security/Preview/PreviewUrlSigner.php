<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Psr\Clock\ClockInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class PreviewUrlSigner implements PreviewUrlSignerInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly UriSigner $uriSigner,
        private readonly RequestStack $requestStack,
        private readonly ClockInterface $clock,
        private readonly int $ttl,
    ) {
    }

    public function sign(string $url, CmsRoutableInterface $subject, string $resource, string $locale): string
    {
        if (!$this->authorizationChecker->isGranted(ContentDocumentVoter::PAGE_BUILDER, $subject)) {
            throw new PreviewSigningDeniedException('The current user may not preview this content in the page builder.');
        }

        $id = $subject instanceof ResourceInterface ? $subject->getId() : null;
        if (!is_int($id) && !is_string($id)) {
            throw new PreviewSigningDeniedException('Only a persisted resource can be previewed with a signed URL.');
        }

        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            throw new PreviewSigningDeniedException('A signed preview URL can only be produced during an admin request.');
        }

        if (str_starts_with($url, '//')) {
            $url = $request->getScheme() . ':' . $url;
        } elseif (str_starts_with($url, '/')) {
            $url = $request->getSchemeAndHttpHost() . $url;
        }

        $fragment = null;
        if (str_contains($url, '#')) {
            [$url, $fragment] = explode('#', $url, 2);
        }

        [$path, $queryString] = array_pad(explode('?', $url, 2), 2, '');

        parse_str($queryString, $query);
        $query[SignedPreviewParameters::PREVIEW] = '1';
        $query[SignedPreviewParameters::RESOURCE] = $resource;
        $query[SignedPreviewParameters::ID] = (string) $id;
        $query[SignedPreviewParameters::LOCALE] = $locale;
        $query[SignedPreviewParameters::ADMIN_HOST] = $request->getSchemeAndHttpHost();

        $url = $path . '?' . http_build_query($query, '', '&') . (null !== $fragment ? '#' . $fragment : '');

        return $this->uriSigner->sign($url, $this->clock->now()->modify(sprintf('+%d seconds', $this->ttl)));
    }
}
