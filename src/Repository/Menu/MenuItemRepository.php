<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;

/**
 * @extends NestedTreeRepository<MenuItemInterface>
 */
class MenuItemRepository extends NestedTreeRepository implements MenuItemRepositoryInterface
{
    use ResourceRepositoryTrait;
    use TranslationRepositoryTrait;

    protected bool $cacheEnabled = false;

    protected int $cacheTtl;

    public function __construct(EntityManagerInterface $em, string $className)
    {
        $class = $this->manager->getClassMetadata(get_class($className));
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
        $qb = $this->createQueryBuilder('menuitem')
            ->where('menuitem.publishState = :state')
            ->andWhere('menuitem.publishDate < :publishDate');

        $orModule = $qb->expr()->orx();
        $orModule->add($qb->expr()->gt('menuitem.unpublishDate', ':unpublishDate'));
        $orModule->add($qb->expr()->isNull('menuitem.unpublishDate'));

        $qb->andWhere($orModule);

        $qb->setParameter('state', ThreeStateStatusEnum::PUBLISHED());
        $qb->setParameter('publishDate', new \DateTime());
        $qb->setParameter('unpublishDate', new \DateTime());

        return $qb;
    }

    /**
     * @return array<MenuItemInterface>|QueryBuilder ($returnQueryBuilder ? QueryBuilder : MenuItem[])
     */
    public function getPublished(bool $returnQueryBuilder = false): array|QueryBuilder
    {
        $qb = $this->getPublishedQuery();
        if ($returnQueryBuilder) {
            return $qb;
        }

        if ($this->cacheEnabled) {
            $qb = $qb->getQuery()->enableResultCache($this->cacheTtl);
        } else {
            $qb = $qb->getQuery()->disableResultCache();
        }

        return $qb->getResult();
    }

    /**
     * @return array<MenuItemInterface>|QueryBuilder ($returnQueryBuilder ? QueryBuilder : MenuItem[])
     */
    public function getByMenu(Menu $menu, bool $returnQueryBuilder = false): array|QueryBuilder
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere('menuitem.menu = :menu')
            ->setParameter('menu', $menu);
        if ($returnQueryBuilder) {
            return $qb;
        }

        if ($this->cacheEnabled) {
            $qb = $qb->getQuery()->enableResultCache($this->cacheTtl);
        } else {
            $qb = $qb->getQuery()->disableResultCache();
        }

        return $qb->getResult();
    }

    public function filterByMenu(int $menuId, string $locale): QueryBuilder
    {
        return $this->createListQueryBuilder($locale)
            ->andWhere('entity.menu = :menu')
            ->setParameter('menu', $menuId);
    }

    public function findPreviousMenuItem(MenuItemInterface $menuItem): ?MenuItemInterface
    {
        return $this->createQueryBuilder('mi')
            ->andWhere('mi.id != :id')
            ->andWhere('mi.lvl = :level')
            ->andWhere('mi.position < :position')
            ->andWhere('mi.position IS NOT NULL')
            ->setParameter('id', $menuItem->getId())
            ->setParameter('level', $menuItem->getLvl())
            ->setParameter('position', $menuItem->getPosition())
            ->orderBy('mi.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }

    public function findNextMenuItem(MenuItemInterface $menuItem): ?MenuItemInterface
    {
        return $this->createQueryBuilder('mi')
            ->andWhere('mi.id != :id')
            ->andWhere('mi.lvl = :level')
            ->andWhere('mi.position > :position')
            ->andWhere('mi.position IS NOT NULL')
            ->setParameter('id', $menuItem->getId())
            ->setParameter('level', $menuItem->getLvl())
            ->setParameter('position', $menuItem->getPosition())
            ->orderBy('mi.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }
}
