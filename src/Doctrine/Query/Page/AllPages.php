<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Page;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;

final class AllPages implements AllPagesInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LocaleContextInterface $localeContext,
        private readonly TranslationLocaleProviderInterface $translationLocaleProvider,
    ) {
    }

    public function getArrayResult(): array
    {
        $fallbackLocale = $this->translationLocaleProvider->getDefaultLocaleCode();

        try {
            $currentLocale = $this->localeContext->getLocaleCode();
        } catch (LocaleNotFoundException) {
            $currentLocale = $fallbackLocale;
        }

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select(
                'page.id as id',
                'page.root as tree_root',
                'page.parent_id as parent_id',
                'COALESCE(current_translation.slug, fallback_translation.slug) as code',
                'page.lft as tree_left',
                'page.rgt as tree_right',
                'page.lvl as tree_level',
                'page.position as position',
                'COALESCE(current_translation.name, fallback_translation.name) as name',
            )
            ->from('sylius_happy_cms__page', 'page')
            ->leftJoin(
                'page',
                'sylius_happy_cms__page_translation',
                'current_translation',
                (string) $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('current_translation.translatable_id', 'page.id'),
                    $queryBuilder->expr()->eq('current_translation.locale', ':currentLocale'),
                ),
            )
            ->leftJoin(
                'page',
                'sylius_happy_cms__page_translation',
                'fallback_translation',
                (string) $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('fallback_translation.translatable_id', 'page.id'),
                    $queryBuilder->expr()->eq('fallback_translation.locale', ':fallbackLocale'),
                ),
            )
            ->orderBy('page.lvl', Criteria::DESC)
            ->addOrderBy('page.root', Criteria::ASC)
            ->addOrderBy('page.lft', Criteria::ASC)
            ->setParameter('currentLocale', $currentLocale, Types::STRING)
            ->setParameter('fallbackLocale', $fallbackLocale, Types::STRING)
        ;

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
}
