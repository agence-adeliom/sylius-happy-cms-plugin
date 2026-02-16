<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Menu;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;

final class AllMenuItems implements AllMenuItemsInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LocaleContextInterface $localeContext,
        private readonly TranslationLocaleProviderInterface $translationLocaleProvider,
    ) {
    }

    public function getArrayResult(int $menuId): array
    {
        $fallbackLocale = $this->translationLocaleProvider->getDefaultLocaleCode();

        try {
            $currentLocale = $this->localeContext->getLocaleCode();
        } catch (LocaleNotFoundException) {
            $currentLocale = $fallbackLocale;
        }

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select([
                'menu_item.id as id',
                'menu_item.root as tree_root',
                'menu_item.parent_id as parent_id',
                'menu_item.lft as tree_left',
                'menu_item.rgt as tree_right',
                'menu_item.lvl as tree_level',
                'menu_item.position as position',
                'COALESCE(current_translation.name, fallback_translation.name) as name',
            ])
            ->from('sylius_happy_cms__menu_item', 'menu_item')
            ->leftJoin(
                'menu_item',
                'sylius_happy_cms__menu_item_translation',
                'current_translation',
                (string) $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('current_translation.translatable_id', 'menu_item.id'),
                    $queryBuilder->expr()->eq('current_translation.locale', ':currentLocale'),
                ),
            )
            ->leftJoin(
                'menu_item',
                'sylius_happy_cms__menu_item_translation',
                'fallback_translation',
                (string) $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('fallback_translation.translatable_id', 'menu_item.id'),
                    $queryBuilder->expr()->eq('fallback_translation.locale', ':fallbackLocale'),
                ),
            )
            ->andWhere('menu_item.menu_id = :menuId')
            ->orderBy('menu_item.lvl', Order::Descending->value)
            ->addOrderBy('menu_item.root', Order::Ascending->value)
            ->addOrderBy('menu_item.lft', Order::Ascending->value)
            ->setParameter('currentLocale', $currentLocale, Types::STRING)
            ->setParameter('fallbackLocale', $fallbackLocale, Types::STRING)
            ->setParameter('menuId', $menuId, Types::INTEGER)
        ;

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
}
