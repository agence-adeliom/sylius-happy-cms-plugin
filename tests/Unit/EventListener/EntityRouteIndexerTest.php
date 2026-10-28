<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Sluggable\SluggableListener;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\CategoryPathResolver;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing\IndexedPage;
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
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}']);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about', '/'], array_values($this->storedPrefixes()));
    }

    public function testThePathOfAParentOfAnotherTypeFillsItsPlaceholder(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}']);
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
        $this->registerIndexer(['collapseSlashes' => true]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/' . $event->getEntity()->category . '/');
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/en_US/company/about'], array_values($this->storedPrefixes()));
    }

    public function testTheRoutesCanEndWithASlash(): void
    {
        $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'trailingSlash' => true]);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about/', '/'], array_values($this->storedPrefixes()));
    }

    public function testTheFirstSupportingParentPathResolverPlacesTheRouteUnderAParentOfAnotherType(): void
    {
        $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'parentPathResolvers' => [
            new CategoryPathResolver('/never'),
            new CategoryPathResolver(),
            new CategoryPathResolver('/too-late'),
        ]]);

        $this->savePage('about', category: 'company');
        $this->savePage('contact');

        self::assertSame(['/company/about', '/contact'], array_values($this->storedPrefixes()));
    }

    public function testListenersReceiveTheResolvedParentPath(): void
    {
        $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'parentPathResolvers' => [new CategoryPathResolver()]]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/shop' . $event->getRouteStaticPrefix());
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/shop/company/about'], array_values($this->storedPrefixes()));
    }

    public function testWithoutIndexOnFlushTheRoutesOfANewEntityAreWrittenByANestedFlush(): void
    {
        $this->registerIndexer();
        $flushes = $this->countFlushes();

        $about = $this->savePage('about');

        self::assertSame(2, $flushes->count, 'Legacy listener: the routes are written by a flush inside postPersist.');
        self::assertSame(['route_online_en_US_' . str_replace('\\', '_', IndexedPage::class) . '_' . $about->getId() => '/en_US/about'], $this->storedPrefixes());
    }

    public function testWithoutIndexOnFlushAnEntityIsIndexedOncePerProcess(): void
    {
        $this->registerIndexer();
        $page = $this->savePage('about');

        $page->getTranslation()->setSlug('about-us');
        $this->entityManager->flush();

        self::assertSame(['/en_US/about'], array_values($this->storedPrefixes()), 'Legacy listener: the route of an entity already indexed in the process is not recomputed.');
    }

    public function testNewEntitiesGetTheirRouteNameAndContentIdWithinASingleFlush(): void
    {
        $this->registerIndexer(['indexOnFlush' => true]);
        $flushes = $this->countFlushes();

        $about = new IndexedPage('about');
        $contact = new IndexedPage('contact');
        $this->entityManager->persist($about);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        self::assertSame(1, $flushes->count, 'The routes are written by the flush that saves the entities.');
        $routeName = static fn (IndexedPage $page): string => 'route_online_en_US_' . str_replace('\\', '_', IndexedPage::class) . '_' . $page->getId();
        self::assertSame([$routeName($about) => '/en_US/about', $routeName($contact) => '/en_US/contact'], $this->storedPrefixes());
        $defaults = $this->entityManager->getConnection()->fetchOne('SELECT defaults FROM indexed_route WHERE name = ?', [$routeName($about)]);
        self::assertSame(IndexedPage::class . ':' . $about->getId(), json_decode((string) $defaults, true)['_content_id']);

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        foreach ([...$about->getRoutes(), ...$contact->getRoutes()] as $route) {
            self::assertSame([], $unitOfWork->getEntityChangeSet($route), 'The completed name and content id are known as persisted.');
        }
    }

    public function testChangingASlugUpdatesTheRouteOfAnEntityAlreadyIndexedInTheSameProcess(): void
    {
        $this->registerIndexer(['indexOnFlush' => true]);
        $page = $this->savePage('about');

        $page->getTranslation()->setSlug('about-us');
        $this->entityManager->flush();

        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()));
    }

    public function testChangingTheSlugOfAReloadedEntityUpdatesItsRoute(): void
    {
        $this->registerIndexer(['indexOnFlush' => true]);
        $id = $this->savePage('about')->getId();
        $this->entityManager->clear();

        $page = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $page);
        $page->getTranslation()->setSlug('about-us');
        $this->entityManager->flush();

        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()));
        self::assertCount(1, $page->getRoutes());
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function indexOnFlushModes(): iterable
    {
        yield 'legacy listener' => [false];
        yield 'index on flush' => [true];
    }

    #[DataProvider('indexOnFlushModes')]
    public function testIndexComputesTheRoutesOfAnEntityOutsideOfAFlush(bool $indexOnFlush): void
    {
        $indexer = $this->registerIndexer(['indexOnFlush' => $indexOnFlush]);
        $page = $this->savePage('about');
        $this->entityManager->getConnection()->executeStatement('UPDATE indexed_route SET staticPrefix = ?', ['/stale']);
        $this->entityManager->clear();
        $page = $this->entityManager->find(IndexedPage::class, $page->getId());
        self::assertInstanceOf(IndexedPage::class, $page);
        $flushes = $this->countFlushes();

        $indexer->index($page);

        self::assertSame(0, $flushes->count, 'index() does not flush.');
        self::assertSame(['/stale'], array_values($this->storedPrefixes()));

        $this->entityManager->flush();

        self::assertSame(['/en_US/about'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testRewriteStaticPrefixMovesTheRoutesBelowAPathOutsideOfAFlush(bool $indexOnFlush): void
    {
        $indexer = $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'indexOnFlush' => $indexOnFlush, 'parentPathResolvers' => [new CategoryPathResolver()]]);
        $this->savePage('wooden', category: 'doors');
        $this->savePage('contact');
        $flushes = $this->countFlushes();

        $indexer->rewriteStaticPrefix('/doors', '/entry-doors');

        self::assertSame(0, $flushes->count, 'rewriteStaticPrefix() does not flush.');
        $this->entityManager->flush();

        self::assertSame(['/entry-doors/wooden', '/contact'], array_values($this->storedPrefixes()));
    }

    public function testAnEntityCreatedWithoutSlugGetsARouteWithTheSlugGeneratedInTheSameFlush(): void
    {
        $this->registerIndexerAndSluggableListenerInServiceOrder();

        $page = new IndexedPage(null, name: 'About us');
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        self::assertSame('about-us', $page->getTranslation()->getSlug());
        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()));
    }

    public function testWithoutIndexOnFlushAnEntityCreatedWithoutSlugGetsARouteWithTheGeneratedSlug(): void
    {
        $sluggable = new SluggableListener();
        $sluggable->setAnnotationReader(new AttributeReader());
        $this->entityManager->getEventManager()->addEventSubscriber($sluggable);
        $this->registerIndexer();

        $page = new IndexedPage(null, name: 'About us');
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()), 'Legacy listener: postPersist runs after the slug is generated.');
    }

    public function testRenamingAPrefixRewritesTheDescendantRoutesOnly(): void
    {
        $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'parentPathResolvers' => [new CategoryPathResolver()], 'indexOnFlush' => true, 'childRoutesPrefixBoundary' => true]);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', category: 'porte');
        $this->savePage('vitree', category: 'porte/porte');

        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail', '/portes-d-entree', '/portail/porte-bois', '/portail/porte/vitree'], array_values($this->storedPrefixes()));
    }

    public function testRenamingAPrefixWithATrailingSlashRewritesTheDescendantRoutesOnly(): void
    {
        $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'trailingSlash' => true, 'parentPathResolvers' => [new CategoryPathResolver()], 'indexOnFlush' => true, 'childRoutesPrefixBoundary' => true]);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', category: 'porte');

        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/', '/portes-d-entree/', '/portail/porte-bois/'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testWithThePrefixBoundaryTheRoutesBelowAPathOfAnotherTypeCanBeMoved(bool $indexOnFlush): void
    {
        $indexer = $this->registerIndexer(['urlPattern' => '{{other_entity_path}}{{current_entity_path}}', 'parentPathResolvers' => [new CategoryPathResolver()], 'indexOnFlush' => $indexOnFlush, 'childRoutesPrefixBoundary' => true]);
        $this->savePage('porte-bois', category: 'porte');
        $this->savePage('portes-d-entree');

        $indexer->rewriteStaticPrefix('/porte', '/portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/porte-bois', '/portes-d-entree'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testWithThePrefixBoundaryTheLegacyListenerRewritesTheDescendantRoutesOnly(bool $indexOnFlush): void
    {
        $this->registerIndexer(['indexOnFlush' => $indexOnFlush, 'childRoutesPrefixBoundary' => true]);
        $this->listenCategoryPath();
        $id = $this->savePage('porte')->getId();
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', category: 'porte');
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/en_US/portail', '/en_US/portes-d-entree', '/en_US/portail/porte-bois'], array_values($this->storedPrefixes()));
    }

    #[DataProvider('indexOnFlushModes')]
    public function testWithoutThePrefixBoundaryEveryRouteStartingWithTheRenamedPathIsRewritten(bool $indexOnFlush): void
    {
        $this->registerIndexer(['indexOnFlush' => $indexOnFlush]);
        $this->listenCategoryPath();
        $id = $this->savePage('porte')->getId();
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', category: 'porte');
        $this->restartProcess();

        $door = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $door);
        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/en_US/portail', '/en_US/portails-d-entree', '/en_US/portail/porte-bois'], array_values($this->storedPrefixes()), 'Legacy rewriting: a sibling sharing the first characters is rewritten too.');
    }

    /**
     * Places a page under "/en_US/<category>" (CalculateRouteStaticPrefixEvent, available without the options).
     */
    private function listenCategoryPath(): void
    {
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $category = $event->getEntity()->category;
            $event->setRouteStaticPrefix(null === $category ? '' : '/' . $category);
        });
    }
}
