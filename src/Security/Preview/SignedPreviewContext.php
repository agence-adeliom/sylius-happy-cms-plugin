<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

/**
 * What a valid signed preview request allows: one resource, one id, one locale, framed by one admin origin.
 */
final readonly class SignedPreviewContext
{
    public function __construct(
        public string $resource,
        public int $id,
        public string $locale,
        public string $adminOrigin,
    ) {
    }
}
