<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Thrown when the current user may not edit the content, so no signed preview URL is produced.
 */
class PreviewSigningDeniedException extends AccessDeniedException
{
}
