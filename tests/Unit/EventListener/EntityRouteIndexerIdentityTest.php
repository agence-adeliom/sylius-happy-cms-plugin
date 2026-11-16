<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf\Route;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\MakerShapedEntity;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\MakerShapedEntityTranslation;

/**
 * The indexer remembers the entities it indexes during a flush, so that its own flush does not index them a second
 * time. It recognises them by identity and forgets them once the flush is over.
 */
final class EntityRouteIndexerIdentityTest extends TestCase
{
    private int $flushes = 0;

    /** @var (\Closure(): void)|null */
    private ?\Closure $duringFlush = null;

    public function testAPageSavedAgainAfterAFlushGetsItsRoutesRecomputed(): void
    {
        $page = $this->page('first-page');
        [$indexer, $entityManager] = $this->indexer();

        $indexer->postUpdate(new PostUpdateEventArgs($page, $entityManager));
        $indexer->postFlush(new PostFlushEventArgs($entityManager));
        $indexer->postUpdate(new PostUpdateEventArgs($page, $entityManager));

        self::assertSame(2, $this->flushes);
    }

    public function testTwoEntitiesMadeByTheMakerAreBothIndexed(): void
    {
        // The maker puts the translations first: comparing two entities by value walks entity, translation, entity…
        $first = $this->article('first-article');
        $second = $this->article('second-article');
        [$indexer, $entityManager] = $this->indexer();

        $indexer->postUpdate(new PostUpdateEventArgs($first, $entityManager));
        $indexer->postUpdate(new PostUpdateEventArgs($second, $entityManager));

        self::assertSame(2, $this->flushes);
    }

    public function testAPageIsIndexedOnceDuringTheFlushThatSavesIt(): void
    {
        $page = $this->page('first-page');
        [$indexer, $entityManager] = $this->indexer();

        // The indexer flushes the routes it computed: that flush reports the page as updated again, then ends.
        $this->duringFlush = function () use ($indexer, $entityManager, $page): void {
            $indexer->postUpdate(new PostUpdateEventArgs($page, $entityManager));
            $indexer->postFlush(new PostFlushEventArgs($entityManager));
        };

        $indexer->postUpdate(new PostUpdateEventArgs($page, $entityManager));
        // Still within the flush that saves the page: its translation is reported too.
        $indexer->postUpdate(new PostUpdateEventArgs($page->getTranslation('fr_FR'), $entityManager));

        self::assertSame(1, $this->flushes);
    }

    public function testTheIndexerListensToTheEndOfTheFlush(): void
    {
        $pluginConfig = Yaml::parseFile(__DIR__ . '/../../../config/packages/sylius_happy_cms.yaml');
        self::assertIsArray($pluginConfig);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms']], $container);

        $events = array_column(
            $container->getDefinition('sylius.happy_cms.plugin.listener.entity_route_indexer')->getTag('doctrine.event_listener'),
            'event',
        );

        self::assertContains('postFlush', $events);
    }

    /**
     * @return array{EntityRouteIndexer, EntityManagerInterface}
     */
    private function indexer(): array
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willReturnCallback(function (): void {
            ++$this->flushes;
            if ($this->flushes > 5) {
                self::fail('The indexer keeps flushing.');
            }
            $duringFlush = $this->duringFlush;
            $this->duringFlush = null;
            if (null !== $duringFlush) {
                $duringFlush();
            }
        });

        $indexer = new EntityRouteIndexer(
            $this->createStub(ContentRepository::class),
            new ParameterBag(['cmf_routing.dynamic.persistence.orm.route_class' => Route::class]),
            new EventDispatcher(),
            $entityManager,
        );

        return [$indexer, $entityManager];
    }

    private function article(string $slug): MakerShapedEntity
    {
        $article = new MakerShapedEntity();
        $article->setCurrentLocale('fr_FR');
        $article->setFallbackLocale('fr_FR');
        $translation = $article->getTranslation('fr_FR');
        self::assertInstanceOf(MakerShapedEntityTranslation::class, $translation);
        $translation->setSlug($slug);

        return $article;
    }

    private function page(string $slug): Page
    {
        $page = new Page();
        $page->setCurrentLocale('fr_FR');
        $page->setFallbackLocale('fr_FR');
        $translation = $page->getTranslation('fr_FR');
        self::assertInstanceOf(PageTranslationInterface::class, $translation);
        $translation->setSlug($slug);

        return $page;
    }
}
