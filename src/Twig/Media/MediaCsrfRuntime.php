<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\CsrfProtection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CSRF token of the media manager, for the admin pages that call the media routes without being the media manager
 * page (a form with a media field, a media picker).
 */
class MediaCsrfRuntime implements RuntimeExtensionInterface
{
    use CsrfProtection;

    public function __construct(
        protected RequestStack $requestStack,
        protected TranslatorInterface $translator,
    ) {
    }

    public function csrfToken(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return '';
        }

        return $this->getCsrfToken($request);
    }
}
