<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Session-based CSRF protection for the media manager.
 *
 * Single source of truth for media CSRF tokens: used both by the media
 * controller (validation on upload/move/etc.) and by the Twig layer to
 * render the <meta name="csrf-token"> tag on every admin page.
 *
 * The token is a stable session token (not single-use): it is reused for the
 * whole lifetime, so several consecutive uploads share the same token.
 */
final class MediaCsrfTokenManager
{
    /**
     * Session key for storing the CSRF token.
     */
    public const CSRF_TOKEN_SESSION_KEY = '_media_csrf_token';

    /**
     * Token lifetime in seconds (default: 2 hours).
     */
    public const CSRF_TOKEN_LIFETIME = 7200;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Get the current CSRF token from session.
     * Creates a new one if none exists or if expired.
     */
    public function getToken(): string
    {
        $session = $this->getSession();
        $tokenData = $session->get(self::CSRF_TOKEN_SESSION_KEY);

        if (
            is_array($tokenData) &&
            isset($tokenData['token'], $tokenData['timestamp']) &&
            (time() - $tokenData['timestamp']) < self::CSRF_TOKEN_LIFETIME
        ) {
            return $tokenData['token'];
        }

        return $this->generateToken();
    }

    /**
     * Generate a new CSRF token and store it in session.
     */
    public function generateToken(): string
    {
        $session = $this->getSession();

        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32));

        $session->set(self::CSRF_TOKEN_SESSION_KEY, [
            'token' => $token,
            'timestamp' => time(),
        ]);

        return $token;
    }

    /**
     * Validate the CSRF token from the request.
     *
     * @param string|null $tokenKey The key to look for the token (default: '_csrf_token')
     *
     * @throws BadRequestException If token is invalid or missing
     */
    public function validate(Request $request, ?string $tokenKey = '_csrf_token'): bool
    {
        $session = $this->getSession();

        $submittedToken = null;

        // Try to get from JSON body first
        $content = $request->getContent();
        if (!empty($content)) {
            try {
                $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
                if (is_array($data) && isset($data[$tokenKey])) {
                    $submittedToken = $data[$tokenKey];
                }
            } catch (\JsonException $e) {
                // Not JSON, try other methods
            }
        }

        // Fallback to POST parameters
        if (!$submittedToken && is_string($tokenKey)) {
            $submittedToken = $request->request->get($tokenKey);
        }

        // Fallback to headers
        if (!$submittedToken) {
            $submittedToken = $request->headers->get('X-CSRF-Token');
        }

        // Check if token was submitted
        if (!$submittedToken || !is_string($submittedToken)) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_missing', [], 'SyliusHappyCMSPlugin'),
            );
        }

        // Get stored token from session
        $tokenData = $session->get(self::CSRF_TOKEN_SESSION_KEY);

        // Validate token exists
        if (!is_array($tokenData) || !isset($tokenData['token'], $tokenData['timestamp'])) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_invalid', [], 'SyliusHappyCMSPlugin'),
            );
        }

        // Check if token is expired
        if ((time() - $tokenData['timestamp']) >= self::CSRF_TOKEN_LIFETIME) {
            $session->remove(self::CSRF_TOKEN_SESSION_KEY);

            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_expired', [], 'SyliusHappyCMSPlugin'),
            );
        }

        // Use timing-safe comparison to prevent timing attacks
        if (!hash_equals($tokenData['token'], $submittedToken)) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_invalid', [], 'SyliusHappyCMSPlugin'),
            );
        }

        return true;
    }

    /**
     * Get the session from the current request.
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SessionNotFoundException If session is not available
     */
    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
