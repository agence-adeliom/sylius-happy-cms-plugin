<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Routing\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A project controller declaring its routes with attributes, as loaded by
 * `resource: ../src/Controller/, type: attribute` in a project `config/routes.yaml`.
 */
final class AttributeRoutedController
{
    #[Route('/attribute-routed', name: 'test_attribute_routed')]
    public function __invoke(): Response
    {
        return new Response();
    }
}
