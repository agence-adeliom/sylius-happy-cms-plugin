<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
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
}
