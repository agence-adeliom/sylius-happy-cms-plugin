<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class SharedBlockRepository extends EntityRepository implements SharedBlockRepositoryInterface, RepositoryInterface, TranslationRepositoryInterface
{
    use ResourceRepositoryTrait;
    use TranslationRepositoryTrait;

    public function getPublishedQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('block')
            ->where('block.status = :state')
        ;

        $qb->setParameter('state', true);

        return $qb;
    }

    /**
     * @return SharedBlockInterface[]
     */
    public function getActive(): array
    {
        $qb = $this->getPublishedQuery();

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return SharedBlockInterface[]
     */
    public function getByType(string $type): array
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere('block.type = :type')
            ->setParameter('type', $type);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $types
     *
     * @return SharedBlockInterface[]
     */
    public function findByTypes(array $types): array
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere('block.type IN (:types)')
            ->setParameter('types', $types);

        return $qb->getQuery()
            ->getResult();
    }
}
