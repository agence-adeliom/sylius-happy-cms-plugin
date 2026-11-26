<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Symfony\Component\HttpFoundation\Request;

interface SignedPreviewRequestValidatorInterface
{
    /**
     * Returns the context of a preview request whose signature is intact and not expired, null otherwise.
     */
    public function validate(Request $request): ?SignedPreviewContext;
}
