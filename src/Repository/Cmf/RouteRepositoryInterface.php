<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;

interface RouteRepositoryInterface
{
    public function createNew(): RouteInterface;
}
