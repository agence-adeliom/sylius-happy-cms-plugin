<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Event\Route\CalculateRouteStaticPrefixEvent;
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

    public function testTheRoutesCanEndWithASlash(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', true]);

        $this->savePage('about');
        $this->savePage('home', homepage: true);

        self::assertSame(['/about/', '/'], array_values($this->storedPrefixes()));
    }

    public function testTheFirstSupportingParentPathResolverPlacesTheRouteUnderAParentOfAnotherType(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [
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
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()]]);
        $this->dispatcher->addListener(CalculateRouteStaticPrefixEvent::class, static function (CalculateRouteStaticPrefixEvent $event): void {
            $event->setRouteStaticPrefix('/shop' . $event->getRouteStaticPrefix());
        });

        $this->savePage('about', category: 'company');

        self::assertSame(['/shop/company/about'], array_values($this->storedPrefixes()));
    }

    public function testNewEntitiesGetTheirRouteNameAndContentIdWithinASingleFlush(): void
    {
        $this->registerIndexer();
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
        $this->registerIndexer();
        $page = $this->savePage('about');

        $page->getTranslation()->setSlug('about-us');
        $this->entityManager->flush();

        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()));
    }

    public function testChangingTheSlugOfAReloadedEntityUpdatesItsRoute(): void
    {
        $this->registerIndexer();
        $id = $this->savePage('about')->getId();
        $this->entityManager->clear();

        $page = $this->entityManager->find(IndexedPage::class, $id);
        self::assertInstanceOf(IndexedPage::class, $page);
        $page->getTranslation()->setSlug('about-us');
        $this->entityManager->flush();

        self::assertSame(['/en_US/about-us'], array_values($this->storedPrefixes()));
        self::assertCount(1, $page->getRoutes());
    }

    public function testRenamingAPrefixRewritesTheDescendantRoutesOnly(): void
    {
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()]]);
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
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', true, [new CategoryPathResolver()]]);
        $door = $this->savePage('porte');
        $this->savePage('portes-d-entree');
        $this->savePage('porte-bois', category: 'porte');

        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/', '/portes-d-entree/', '/portail/porte-bois/'], array_values($this->storedPrefixes()));
    }

    public function testTheRewritingOfTheRoutesBelowARenamedPathCanBeDisabled(): void
    {
        // a page "porte" and a category "porte" (another type, same slug): "/porte/porte-bois" is a child of the
        // category, not of the page; renaming the page must not move it
        $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()], false]);
        $door = $this->savePage('porte');
        $this->savePage('porte-bois', category: 'porte');

        $door->getTranslation()->setSlug('portail');
        $this->entityManager->flush();

        self::assertSame(['/portail', '/porte/porte-bois'], array_values($this->storedPrefixes()));
    }

    public function testTheRoutesBelowAPathCanStillBeMovedExplicitlyWhenTheRewritingIsDisabled(): void
    {
        $indexer = $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()], false]);
        $this->savePage('porte-bois', category: 'porte');

        $indexer->rewriteStaticPrefix('/porte', '/portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/porte-bois'], array_values($this->storedPrefixes()));
    }

    public function testTheRoutesBelowAPathOfAnotherTypeCanBeMoved(): void
    {
        $indexer = $this->registerIndexer(['{{other_entity_path}}{{current_entity_path}}', false, [new CategoryPathResolver()]]);
        $this->savePage('porte-bois', category: 'porte');
        $this->savePage('portes-d-entree');

        $indexer->rewriteStaticPrefix('/porte', '/portail');
        $this->entityManager->flush();

        self::assertSame(['/portail/porte-bois', '/portes-d-entree'], array_values($this->storedPrefixes()));
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
}
