<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;

interface PreviewUrlGeneratorInterface
{
    /**
     * Generates the preview URL from the route of the entity itself (never from the current channel context), then signs it.
     * Returns an empty string when the entity has no route for the locale.
     *
     * @throws PreviewSigningDeniedException when the current user may not use the page builder on the subject
     */
    public function generate(CmsRoutableInterface $subject, string $resource, string $locale): string;
}
