<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface MenuRepositoryInterface extends RepositoryInterface
{
    public function findOneByCode(string $code): ?MenuInterface;

    /**
     * @param array<string, mixed> $cacheConfig
     */
    public function setConfig(array $cacheConfig): void;
}
