<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Service;

use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;

/**
 * Autowires the repository interfaces of the plugin by type only: the argument names do not match
 * the named aliases Sylius registers for resources (`$pageRepository`, `$routeRepository`).
 */
final class RepositoryInterfacesConsumer
{
    public function __construct(
        public readonly PageRepositoryInterface $pages,
        public readonly RouteRepositoryInterface $routes,
    ) {
    }
}
