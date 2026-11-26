<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\UriSigner;

final class SignedPreviewRequestValidator implements SignedPreviewRequestValidatorInterface
{
    /** Request attribute caching the result: a SignedPreviewContext, or false. */
    public const REQUEST_ATTRIBUTE = '_hcms_signed_preview';

    public function __construct(
        private readonly RequestMatcherInterface $previewRequestMatcher,
        private readonly UriSigner $uriSigner,
    ) {
    }

    public function validate(Request $request): ?SignedPreviewContext
    {
        $cached = $request->attributes->get(self::REQUEST_ATTRIBUTE);
        if ($cached instanceof SignedPreviewContext) {
            return $cached;
        }

        if (false === $cached) {
            return null;
        }

        $context = $this->doValidate($request);
        $request->attributes->set(self::REQUEST_ATTRIBUTE, $context ?? false);

        return $context;
    }

    private function doValidate(Request $request): ?SignedPreviewContext
    {
        if (!$this->previewRequestMatcher->matches($request)) {
            return null;
        }

        $query = $request->query;
        foreach ([
            SignedPreviewParameters::RESOURCE,
            SignedPreviewParameters::ID,
            SignedPreviewParameters::LOCALE,
            SignedPreviewParameters::ADMIN_HOST,
            SignedPreviewParameters::HASH,
            SignedPreviewParameters::EXPIRATION,
        ] as $parameter) {
            if (!is_string($query->all()[$parameter] ?? null) || '' === $query->getString($parameter)) {
                return null;
            }
        }

        if (!$this->uriSigner->checkRequest($request)) {
            return null;
        }

        $id = $query->getString(SignedPreviewParameters::ID);
        $adminOrigin = $query->getString(SignedPreviewParameters::ADMIN_HOST);

        if (1 !== preg_match('/^[1-9]\d*$/', $id) || !self::isOrigin($adminOrigin)) {
            return null;
        }

        return new SignedPreviewContext(
            $query->getString(SignedPreviewParameters::RESOURCE),
            (int) $id,
            $query->getString(SignedPreviewParameters::LOCALE),
            $adminOrigin,
            self::adminLocale($query->all()[SignedPreviewParameters::ADMIN_LOCALE] ?? null),
        );
    }

    /**
     * The admin locale is optional (an older signed URL does not carry it) and is never trusted blindly:
     * anything that does not look like a locale code is dropped.
     */
    private static function adminLocale(mixed $value): ?string
    {
        if (is_string($value) && 1 === preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $value)) {
            return $value;
        }

        return null;
    }

    private static function isOrigin(string $origin): bool
    {
        $parts = parse_url($origin);
        if (false === $parts) {
            return false;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';

        // Rebuilding the origin from scheme, host and port rejects any path, query, fragment or credentials.
        return in_array($scheme, ['http', 'https'], true) &&
            '' !== $host &&
            $origin === $scheme . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
