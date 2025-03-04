<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Page;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @implements RepositoryInterface<PageInterface>
 */
class PageRepository extends EntityRepository implements PageRepositoryInterface, RepositoryInterface, TranslationRepositoryInterface
{
    use TranslationRepositoryTrait;

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

    public function getPublishedQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('page')
            ->where('page.publishState = :state')
        ;

        $orModule = $qb->expr()->orx();
        $orModule->add($qb->expr()->lte('page.publishDate', ':publishDate'));
        $orModule->add($qb->expr()->isNull('page.publishDate'));

        $qb->andWhere($orModule);

        $orModule = $qb->expr()->orx();
        $orModule->add($qb->expr()->gte('page.unpublishDate', ':unpublishDate'));
        $orModule->add($qb->expr()->isNull('page.unpublishDate'));

        $qb->andWhere($orModule);

        $qb->setParameter('state', ThreeStateStatusEnum::PUBLISHED());
        $qb->setParameter('publishDate', new \DateTime());
        $qb->setParameter('unpublishDate', new \DateTime());

        return $qb;
    }

    public function getHomePage(string $locale, ?ChannelInterface $channel = null): ?PageInterface
    {
        $qb = $this->getPublishedQuery();

        $qb->addSelect('translation');

        $qb->innerJoin('page.translations', 'translation', 'WITH', 'translation.locale = :locale');
        $qb->setParameter('locale', $locale);

        $qb->andWhere('page.template = :template');
        $qb->setParameter('template', PageInterface::HOMEPAGE);

        if (null !== $channel) {
            $qb->andWhere('page.channel = :channel');
            $qb->setParameter('channel', $channel);
        }

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @return PageInterface[]
     */
    public function getPublished(): array
    {
        $qb = $this->getPublishedQuery();

        return $this->getResult($qb->getQuery());
    }

    /**
     * @return PageInterface[]
     */
    private function getResult(Query $query): array
    {
        if ($this->cacheEnabled) {
            return $query->enableResultCache($this->cacheTtl)->getResult();
        }

        return $query->getResult();
    }

    /**
     * @return PageInterface[]
     */
    public function getAllCustom(): array
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere("page.action != ''")
            ->andWhere('page.action IS NOT NULL');

        return $this->getResult($qb->getQuery());
    }

    /**
     * @return PageInterface[]
     */
    public function getByAction(string $action): array
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere('page.action = :action')
            ->setParameter('action', $action);

        return $this->getResult($qb->getQuery());
    }

    /**
     * @return PageInterface[]
     */
    public function getByTemplate(string $template): array
    {
        $qb = $this->getPublishedQuery();
        $qb->andWhere('page.template = :template')
            ->setParameter('template', $template);

        return $this->getResult($qb->getQuery());
    }

    /**
     * @return PageInterface[]
     */
    public function getBySlug(string $slug, string $locale): array
    {
        $qb = $this->getPublishedQuery();

        $qb->addSelect('translation');

        $qb->innerJoin('page.translations', 'translation', 'WITH', 'translation.locale = :locale');
        $qb->setParameter('locale', $locale);

        $qb->andWhere('translation.slug = :slug');
        $qb->setParameter('slug', $slug);

        return $this->getResult($qb->getQuery());
    }

    public function findPreviousPage(PageInterface $page): ?PageInterface
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.id != :id')
            ->andWhere('page.lvl = :level')
            ->andWhere('page.position < :position')
            ->andWhere('page.position IS NOT NULL')
            ->setParameter('id', $page->getId())
            ->setParameter('level', $page->getLvl())
            ->setParameter('position', $page->getPosition() ?? null)
            ->orderBy('page.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findNextPage(PageInterface $page): ?PageInterface
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.id != :id')
            ->andWhere('page.lvl = :level')
            ->andWhere('page.position > :position')
            ->andWhere('page.position IS NOT NULL')
            ->setParameter('id', $page->getId())
            ->setParameter('level', $page->getLvl())
            ->setParameter('position', $page->getPosition() ?? null)
            ->orderBy('page.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * Will search for pages to show in front depending on the arguments.
     * If slugs are defined, there's no problem in looking for nulled host or locale,
     * because slugs are unique, so it does not.
     *
     * @param string[] $slugs
     *
     * @return PageInterface[]
     */
    public function findFrontPages(string $locale, array $slugs = [], ?string $host = null): array
    {
        $qb = $this->getPublishedQuery();
        $allItemsPublished = true;

        // Will search differently if we're looking for homepage.
        $searchForHomepage = [] === $slugs;

        $useConstructedTree = false;
        $constructedTree = [];

        foreach ($this->getBySlug(last($slugs), $locale) as $item) {
            $hasNonPageElement = false;
            $allItemsPublished = true;

            $itemSlug = method_exists($item, 'getPageSlug') ?
                $item->getPageSlug() : (method_exists($item, 'getTranslation') ? $item->getTranslation($locale)->getSlug() : '');
            $tempConstructedTree[$itemSlug] = $item;

            while ($item->getParent()) {
                $item = $item->getParent();
                $itemSlug = $item->getSlug();
                if ($item->getPublishState() === ThreeStateStatusEnum::UNPUBLISHED) {
                    $allItemsPublished = false;
                }
                $tempConstructedTree = array_merge([$itemSlug => $item], $tempConstructedTree);
            }

            $constructedKeys = array_keys($tempConstructedTree);

            if ($constructedKeys !== $slugs) {
                return [];
            }
            $useConstructedTree = true;
            $constructedTree = $tempConstructedTree;

            break;
        }

        if ($useConstructedTree) {
            // If all items in tree (non-pages) are not published, 404
            if (!$allItemsPublished) {
                return [];
            }

            $resultsSortedBySlug = $constructedTree;
            $pages = $constructedTree;
        } else {
            if ($searchForHomepage) {
                // If we are looking for homepage, let's get only the first one.
                $qb
                    ->andWhere('page.template = :template')
                    ->setParameter('template', 'homepage')
                    ->setMaxResults(1)
                ;
            } elseif (1 === count($slugs)) {
                $qb->addSelect('translation');
                $qb->innerJoin('page.translations', 'translation', 'WITH', 'translation.locale = :locale');
                $qb->setParameter('locale', $locale);
                $qb
                    ->andWhere('translation.slug = :slug')
                    ->setParameter('slug', reset($slugs))
                    ->setMaxResults(1)
                ;
            } else {
                $qb->addSelect('translation');
                $qb->innerJoin('page.translations', 'translation', 'WITH', 'translation.locale = :locale');
                $qb->setParameter('locale', $locale);
                $qb
                    ->andWhere('translation.slug IN ( :slugs )')
                    ->setParameter('slugs', $slugs)
                ;
            }

            /** @var PageInterface[] $results */
            $results = $this->getResult($qb->getQuery());

            if ([] === $results) {
                return $results;
            }

            // If we're looking for a homepage, only get the first result (matching more properties).
            if ($searchForHomepage && is_array($results)) {
                reset($results);
                $results = [$results[0]];
            }

            $resultsSortedBySlug = [];
            foreach ($results as $page) {
                $resultsSortedBySlug[$page->getTranslation($locale)->getSlug()] = $page;
            }

            $pages = $resultsSortedBySlug;
        }

        if ([] !== $slugs) {
            $pages = [];
            foreach ($slugs as $value) {
                if (!array_key_exists($value, $resultsSortedBySlug)) {
                    if (array_key_exists($value, $constructedTree)) {
                        $resultsSortedBySlug[$value] = $constructedTree[$value];
                    } else {
                        // Means at least one page in the tree is not enabled
                        return [];
                    }
                }

                $pages[$value] = $resultsSortedBySlug[$value];
            }
        }

        return $pages;
    }
}
