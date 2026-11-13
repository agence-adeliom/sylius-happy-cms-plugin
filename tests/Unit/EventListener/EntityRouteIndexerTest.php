<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPage;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedRoute;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\RouteIndexerTestCase;

final class EntityRouteIndexerTest extends RouteIndexerTestCase
{
    public function testARouteIsCreatedWithTheLocalePrefixByDefault(): void
    {
        $this->registerIndexer();

        $this->savePage('about');

        self::assertSame(['/en_US/about'], array_values($this->storedPrefixes()));
    }

    public function testTheUrlPatternCanLeaveTheLocaleOut(): void
    {
        $this->registerIndexer(['{{calculated_static_prefix}}{{current_entity_path}}']);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about', '/'], array_values($this->storedPrefixes()));
    }

    public function testThePathOfAParentOfAnotherTypeFillsItsPlaceholder(): void
    {
        $this->registerIndexer(['{{calculated_static_prefix}}{{current_entity_path}}']);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category);
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/company/about'], array_values($this->storedPrefixes()));
    }

    public function testRepeatedSlashesAreKeptWithoutTheCollapseOption(): void
    {
        $this->registerIndexer();
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category . '/');
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/en_US/company//about'], array_values($this->storedPrefixes()));
    }

    public function testRepeatedSlashesAreCollapsedWithTheCollapseOption(): void
    {
        $this->registerIndexer([EntityRouteIndexer::DEFAULT_URL_PATTERN, true]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category . '/');
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/en_US/company/about'], array_values($this->storedPrefixes()));
    }

    public function testTheRoutesCanEndWithASlash(): void
    {
        $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}', 'trailingSlash' => true]);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about/', '/'], array_values($this->storedPrefixes()));
    }

    public function testRenamingAPageRecomputesItsDescendantRoutesOnly(): void
    {
        // a page "porte" and a taxon "porte" (another type, same slug): "/porte/coulissante" is a child of the taxon,
        // not of the page; "/portes-d-entree" only shares the first characters
        $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}', 'recomputeChildRoutes' => true]);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $wooden = $this->savePage('porte-bois', parent: $door);
        $this->savePage('vitree', parent: $wooden);
        $this->savePage('coulissante', taxon: 'porte');
        $id = $door->getId();
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail', '/portes-d-entree', '/portail/porte-bois', '/portail/porte-bois/vitree', '/porte/coulissante'], array_values($this->storedPrefixes()));
    }

    public function testRenamingAPageWithATrailingSlashRecomputesItsDescendantRoutesOnly(): void
    {
        $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}', 'trailingSlash' => true, 'recomputeChildRoutes' => true]);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', parent: $door);
        $this->savePage('coulissante', taxon: 'porte');
        $id = $door->getId();
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/', '/portes-d-entree/', '/portail/porte-bois/', '/porte/coulissante/'], array_values($this->storedPrefixes()));
    }

    public function testTheRoutesBelowARenamedPathOfAnotherTypeAreRecomputedExplicitly(): void
    {
        $indexer = $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}', 'recomputeChildRoutes' => true]);
        $this->savePage('porte-bois', taxon: 'porte');
        $this->savePage('portes-d-entree');

        // the taxon (not routable) has its new slug, its pages are recomputed
        IndexedPage::$renamedTaxons['porte'] = 'portail';
        $indexer->rewriteStaticPrefix('/porte', '/portail');

        self::assertSame(['/portail/porte-bois', '/portes-d-entree'], array_values($this->storedPrefixes()));
    }

    public function testARouteWithoutRoutableContentBelowARenamedPathIsMoved(): void
    {
        $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}', 'recomputeChildRoutes' => true]);
        $door = $this->savePage('porte');
        $manual = new IndexedRoute();
        $manual->setName('manual');
        $manual->setStaticPrefix('/porte/manuelle');
        $this->entityManager->persist($manual);
        $this->entityManager->flush();
        $id = $door->getId();
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail', '/portail/manuelle'], array_values($this->storedPrefixes()));
    }

    public function testWithoutRecomputingEveryRouteStartingWithTheRenamedPathIsRewritten(): void
    {
        $this->registerIndexer(['urlPattern' => '{{calculated_static_prefix}}{{current_entity_path}}']);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', parent: $door);
        $this->savePage('coulissante', taxon: 'porte');
        $id = $door->getId();
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail', '/portails-d-entree', '/portail/portail-bois', '/portail/coulissante'], array_values($this->storedPrefixes()), 'Legacy rewriting: a sibling sharing the first characters, every occurrence of the prefix and a child of another type are rewritten.');
    }
}
