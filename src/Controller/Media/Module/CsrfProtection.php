<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Custom CSRF Protection for Media Manager
 *
 * This trait provides CSRF protection using session-based tokens.
 * It's used when Symfony's CSRF component is not available.
 */
trait CsrfProtection
{
    /**
     * Session key for storing CSRF tokens
     */
    private const CSRF_TOKEN_SESSION_KEY = '_media_csrf_token';

    /**
     * Token lifetime in seconds (default: 2 hours)
     */
    private const CSRF_TOKEN_LIFETIME = 7200;

    /**
     * Generate a new CSRF token and store it in session
     *
     * @return string The generated token
     */
    protected function generateCsrfToken(Request $request): string
    {
        $session = $this->getSession($request);

        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32));

        // Store token with timestamp
        $session->set(self::CSRF_TOKEN_SESSION_KEY, [
            'token' => $token,
            'timestamp' => time(),
        ]);

        return $token;
    }

    /**
     * Get the current CSRF token from session
     * Creates a new one if none exists or if expired
     *
     * @return string The current token
     */
    protected function getCsrfToken(Request $request): string
    {
        $session = $this->getSession($request);
        $tokenData = $session->get(self::CSRF_TOKEN_SESSION_KEY);

        // Check if token exists and is not expired
        if (
            is_array($tokenData) &&
            isset($tokenData['token'], $tokenData['timestamp']) &&
            (time() - $tokenData['timestamp']) < self::CSRF_TOKEN_LIFETIME
        ) {
            return $tokenData['token'];
        }

        // Generate new token if none exists or expired
        return $this->generateCsrfToken($request);
    }

    /**
     * Validate CSRF token from request
     *
     * @param Request $request The request containing the token
     * @param string|null $tokenKey The key to look for the token (default: '_csrf_token')
     *
     * @throws BadRequestException If token is invalid or missing
     * @return bool True if valid
     */
    protected function validateCsrfToken(Request $request, ?string $tokenKey = '_csrf_token'): bool
    {
        $session = $this->getSession($request);

        // Get token from request
        $submittedToken = null;

        // Try to get from JSON body first
        $content = $request->getContent();
        if (!empty($content)) {
            try {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data) && isset($data[$tokenKey])) {
                    $submittedToken = $data[$tokenKey];
                }
            } catch (\JsonException $e) {
                // Not JSON, try other methods
            }
        }

        // Fallback to POST parameters
        if (!$submittedToken) {
            $submittedToken = $request->request->get($tokenKey);
        }

        // Fallback to headers
        if (!$submittedToken) {
            $submittedToken = $request->headers->get('X-CSRF-Token');
        }

        // Check if token was submitted
        if (!$submittedToken || !is_string($submittedToken)) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_missing', [], 'SyliusHappyCMSPlugin')
            );
        }

        // Get stored token from session
        $tokenData = $session->get(self::CSRF_TOKEN_SESSION_KEY);

        // Validate token exists
        if (!is_array($tokenData) || !isset($tokenData['token'], $tokenData['timestamp'])) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_invalid', [], 'SyliusHappyCMSPlugin')
            );
        }

        // Check if token is expired
        if ((time() - $tokenData['timestamp']) >= self::CSRF_TOKEN_LIFETIME) {
            // Clear expired token
            $session->remove(self::CSRF_TOKEN_SESSION_KEY);

            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_expired', [], 'SyliusHappyCMSPlugin')
            );
        }

        // Use timing-safe comparison to prevent timing attacks
        if (!hash_equals($tokenData['token'], $submittedToken)) {
            throw new BadRequestException(
                $this->translator->trans('error.csrf_token_invalid', [], 'SyliusHappyCMSPlugin')
            );
        }

        return true;
    }

    /**
     * Get session from request
     *
     * @param Request $request
     * @return SessionInterface
     * @throws \RuntimeException If session is not available
     */
    private function getSession(Request $request): SessionInterface
    {
        $session = $request->getSession();

        if (!$session instanceof SessionInterface) {
            throw new \RuntimeException('Session is not available');
        }

        return $session;
    }

    /**
     * Helper method to refresh the CSRF token
     * Useful after successful operations to prevent token reuse
     *
     * @param Request $request
     * @return void
     */
    protected function refreshCsrfToken(Request $request): void
    {
        $this->generateCsrfToken($request);
    }
}