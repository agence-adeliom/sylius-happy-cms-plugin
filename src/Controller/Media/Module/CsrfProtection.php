<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaCsrfTokenManager;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom CSRF Protection for Media Manager.
 *
 * Thin delegation layer over {@see MediaCsrfTokenManager}, kept for backward
 * compatibility with the controller modules (Upload, Move, Delete, ...) that
 * call these methods. The actual token logic lives in the service so it can be
 * shared with the Twig layer.
 */
trait CsrfProtection
{
    protected MediaCsrfTokenManager $mediaCsrfTokenManager;

    /**
     * Get the current CSRF token from session.
     * Creates a new one if none exists or if expired.
     */
    public function getCsrfToken(Request $request): string
    {
        return $this->mediaCsrfTokenManager->getToken();
    }

    /**
     * Generate a new CSRF token and store it in session.
     */
    protected function generateCsrfToken(Request $request): string
    {
        return $this->mediaCsrfTokenManager->generateToken();
    }

    /**
     * Validate CSRF token from request.
     *
     * @param string|null $tokenKey The key to look for the token (default: '_csrf_token')
     *
     * @throws BadRequestException If token is invalid or missing
     */
    protected function validateCsrfToken(Request $request, ?string $tokenKey = '_csrf_token'): bool
    {
        return $this->mediaCsrfTokenManager->validate($request, $tokenKey);
    }

    /**
     * Helper method to refresh the CSRF token.
     * Useful after successful operations to prevent token reuse.
     */
    protected function refreshCsrfToken(Request $request): void
    {
        $this->mediaCsrfTokenManager->generateToken();
    }
}
