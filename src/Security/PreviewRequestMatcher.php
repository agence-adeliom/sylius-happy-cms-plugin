<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class PreviewRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    {
        // Match if the 'happy_cms_preview' parameter is set to '1'
        return $request->query->get('happy_cms_preview') === '1';
    }
}
