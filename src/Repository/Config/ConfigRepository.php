<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Config;

use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigInterface;
use Doctrine\ORM\NonUniqueResultException;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ConfigRepository extends EntityRepository implements ConfigRepositoryInterface
{
    use TranslationRepositoryTrait;

    /**
     * @throws NonUniqueResultException
     */
    public function getByKey(string $key): ?ConfigInterface
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.key = :key')
            ->setParameter('key', $key);

        $query = $qb
            ->getQuery();

        $result = $query->getOneOrNullResult();

        return $result instanceof ConfigInterface ? $result : null;
    }
}
