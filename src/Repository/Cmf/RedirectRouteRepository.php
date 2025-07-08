<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @implements RepositoryInterface<RedirectRouteInterface>
 */
class RedirectRouteRepository extends EntityRepository implements RepositoryInterface, RedirectRouteRepositoryInterface
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
        $qb = $this->createQueryBuilder('redirect_route');

        return $qb;
    }

    public function createNew(): RedirectRouteInterface
    {
        $className = $this->getClassName();

        /** @var RedirectRouteInterface $redirectRoute */
        $redirectRoute = new $className();

        return $redirectRoute;
    }

    public function findByHostAndPath(string $host, string $path): ?RedirectRouteInterface
    {
        $qb = $this->createQueryBuilder('redirect_route');
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->eq('redirect_route.host', ':host'),
                $qb->expr()->isNull('redirect_route.host')
            )
        )
        ->andWhere('redirect_route.staticPrefix', ':staticPrefix')
        ->setParameter('host', $host)
        ->setParameter('staticPrefix', $path);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
