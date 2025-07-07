<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @implements RepositoryInterface<RouteInterface>
 */
class RouteRepository extends EntityRepository implements RepositoryInterface, RouteRepositoryInterface
{
    protected bool $cacheEnabled = false;

    protected int $cacheTtl;

    /**
     * @param array<string, mixed> $cacheConfig
     */
    public function setConfig(array $cacheConfig): void
    {
        $this->cacheEnabled = $cacheConfig['enabled'] ?? false;
        $this->cacheTtl = $cacheConfig['ttl'] ?? 0;
    }

    public function getRoutes(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('route');

        return $qb;
    }
}
