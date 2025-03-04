<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;

class MenuRepository extends EntityRepository implements MenuRepositoryInterface
{
    use ResourceRepositoryTrait;

    protected bool $cacheEnabled = false;

    protected int $cacheTtl;

    public function __construct(EntityManagerInterface $em)
    {
        $class = $em->getClassMetadata(MenuInterface::class);
        parent::__construct($em, $class);
    }

    /**
     * @param array<string, mixed> $cacheConfig
     */
    public function setConfig(array $cacheConfig): void
    {
        $this->cacheEnabled = (bool) $cacheConfig['enabled'];
        $this->cacheTtl = (int) $cacheConfig['ttl'];
    }

    public function getPublishedQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('menu')
            ->where('menu.status = :status')
        ;

        $qb->setParameter('status', true);

        return $qb;
    }

    /**
     * @return MenuInterface[]
     */
    public function getPublished(): array
    {
        $qb = $this->getPublishedQuery();

        if ($this->cacheEnabled) {
            $qb = $qb->getQuery()->enableResultCache($this->cacheTtl);
        } else {
            $qb = $qb->getQuery()->disableResultCache();
        }

        return $qb->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCode(string $code): ?MenuInterface
    {
        $qb = $this->getPublishedQuery();

        $qb->andWhere('menu.code = :code');
        $qb->setParameter('code', $code);
        $qb->setMaxResults(1);

        if ($this->cacheEnabled) {
            $qb = $qb->getQuery()->enableResultCache($this->cacheTtl);
        } else {
            $qb = $qb->getQuery()->disableResultCache();
        }

        return $qb->getOneOrNullResult();
    }
}
