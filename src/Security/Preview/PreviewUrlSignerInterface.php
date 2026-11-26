<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;

interface PreviewUrlSignerInterface
{
    /**
     * Completes the URL with the admin scheme (and host for a path), then signs it for this subject, resource and locale.
     *
     * @throws PreviewSigningDeniedException when the current user may not use the page builder on the subject
     */
    public function sign(string $url, CmsRoutableInterface $subject, string $resource, string $locale): string;
}
