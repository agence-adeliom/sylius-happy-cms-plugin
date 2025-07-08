<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;

interface RedirectRouteRepositoryInterface
{
    public function createNew(): RedirectRouteInterface;
    public function findByHostAndPath(string $host, string $path): ?RedirectRouteInterface;
}
