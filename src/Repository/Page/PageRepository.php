<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Repository\Page;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\ResourceRepositoryTrait;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends NestedTreeRepository<PageInterface>
 * @implements RepositoryInterface<PageInterface>
 */
class PageRepository extends NestedTreeRepository implements PageRepositoryInterface, RepositoryInterface, TranslationRepositoryInterface
{
    use ResourceRepositoryTrait;
    use TranslationRepositoryTrait;

    protected bool $cacheEnabled = false;

    protected int $cacheTtl;

    /**
     * @param array{
     *     enabled: ?bool,
     *     ttl: ?int
     * } $cacheConfig
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
        // Don't need to get published object, this function is used to generate url
        // The publish state is tested during page render
        // If still needed, to get only publish page, add some variables to this method
        $qb = $this->createQueryBuilder('page');

        $qb->addSelect('translation');

        $qb->innerJoin('page.translations', 'translation', 'WITH', 'translation.locale = :locale');
        $qb->setParameter('locale', $locale);

        $qb->andWhere(
            $qb->expr()->orX(
                // New way to define homepage (plugin version >= 2.1):
                $qb->expr()->eq('page.homepage', ':homepage'),
                // Keep for backward compatibility (plugin version < 2.1):
                $qb->expr()->eq('page.template', ':template'),
            ),
        );
        $qb->setParameter('homepage', true);
        $qb->setParameter('template', PageInterface::HOMEPAGE);

        if (null !== $channel) {
            $qb->andWhere('page.channel = :channel');
            $qb->setParameter('channel', $channel);
        }

        $qb->setMaxResults(1);

        $query = $qb->getQuery();

        /** @var ?PageInterface $result */
        $result = $query->getOneOrNullResult();

        return $result;
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
            /** @var PageInterface[] $result */
            $result = $query->enableResultCache($this->cacheTtl)->getResult();

            return $result;
        }

        /** @var PageInterface[] $result */
        $result = $query->getResult();

        return $result;
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

    public function getByTemplate(string $template, string $locale, ChannelInterface $channel): ?PageInterface
    {
        // Don't need to get published object, this function is used to generate url
        // The publish state is tested during page render
        // If still needed, to get only publish page, add some variables to this method
        $qb = $this->createQueryBuilder('page');
        /** @var PageInterface|null $page */
        $page = $qb
            ->innerJoin('page.translations', 't', 'WITH', 't.locale = :locale')
            ->andWhere('page.template = :template')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('page.channel', ':channel'),
                $qb->expr()->isNull('page.channel'),
            ))
            ->setParameter('template', $template)
            ->setParameter('locale', $locale)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getSingleResult();

        return $page;
    }

    public function getById(int $id, string $locale, ChannelInterface $channel): ?PageInterface
    {
        // Don't need to get published object, this function is used to generate url
        // The publish state is tested during page render
        // If still needed, to get only publish page, add some variables to this method
        $qb = $this->createQueryBuilder('page');
        /** @var PageInterface|null $page */
        $page = $qb
            ->innerJoin('page.translations', 't', 'WITH', 't.locale = :locale')
            ->andWhere('page.id = :id')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('page.channel', ':channel'),
                $qb->expr()->isNull('page.channel'),
            ))
            ->setParameter('id', $id)
            ->setParameter('locale', $locale)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getSingleResult();

        return $page;
    }

    public function getBySeoKey(string $seoKey, string $locale): ?PageInterface
    {
        // Don't need to get published object, this function is used to generate url
        // The publish state is tested during page render
        // If still needed, to get only publish page, add some variables to this method
        $qb = $this->createQueryBuilder('page');
        /** @var PageInterface|null $page */
        $page = $qb
            ->innerJoin('page.translations', 't', 'WITH', 't.locale = :locale')
            ->andWhere('t.seo.key = :seo_key')
            ->setParameter('seo_key', $seoKey)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $page;
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
        $query = $this->createQueryBuilder('page')
            ->andWhere('page.id != :id')
            ->andWhere('page.lvl = :level')
            ->andWhere('page.position < :position')
            ->andWhere('page.position IS NOT NULL')
            ->setParameter('id', $page->getId())
            ->setParameter('level', $page->getLvl())
            ->setParameter('position', $page->getPosition() ?? null)
            ->orderBy('page.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1);

        /** @var ?PageInterface $result */
        $result = $query->getOneOrNullResult();

        return $result;
    }

    public function findNextPage(PageInterface $page): ?PageInterface
    {
        $query = $this->createQueryBuilder('page')
            ->andWhere('page.id != :id')
            ->andWhere('page.lvl = :level')
            ->andWhere('page.position > :position')
            ->andWhere('page.position IS NOT NULL')
            ->setParameter('id', $page->getId())
            ->setParameter('level', $page->getLvl())
            ->setParameter('position', $page->getPosition() ?? null)
            ->orderBy('page.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1);

        /** @var ?PageInterface $result */
        $result = $query->getOneOrNullResult();

        return $result;
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
        $allItemsPublished = null;

        // Will search differently if we're looking for homepage.
        $searchForHomepage = [] === $slugs;

        $useConstructedTree = false;
        $constructedTree = [];

        /** @var string $lastSlug */
        $lastSlug = last($slugs);

        foreach ($this->getBySlug($lastSlug, $locale) as $item) {
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
