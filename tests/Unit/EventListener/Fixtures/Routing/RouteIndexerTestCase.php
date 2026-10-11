<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Sluggable\SluggableListener;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Runs EntityRouteIndexer against a real Doctrine unit of work (SQLite in memory).
 */
abstract class RouteIndexerTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;

    protected EventDispatcher $dispatcher;

    private ?EntityRouteIndexer $indexer = null;

    /** @var array<int|string, mixed> */
    private array $indexerArguments = [];

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $vendor = dirname(__DIR__, 5) . '/vendor';
        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $driver = new MappingDriverChain();
        $attributes = new AttributeDriver([__DIR__, dirname(__DIR__, 5) . '/src/Entity/Cmf']);
        $driver->addDriver($attributes, __NAMESPACE__);
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity\Cmf');
        $driver->addDriver(new SimplifiedXmlDriver([$vendor . '/symfony-cmf/routing-bundle/src/Resources/config/doctrine-model' => 'Symfony\Cmf\Bundle\RoutingBundle\Model']), 'Symfony\Cmf\Bundle\RoutingBundle\Model');
        $driver->addDriver(new XmlDriver($vendor . '/symfony-cmf/routing-bundle/src/Resources/config/doctrine-base', '.orm.xml'), 'Symfony\Component\Routing');
        $config->setMetadataDriverImpl($driver);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(ChannelInterface::class, IndexedChannel::class, []);
        $resolveTargetEntities->addResolveTargetEntity(RouteInterface::class, IndexedRoute::class, []);
        $this->entityManager->getEventManager()->addEventSubscriber($resolveTargetEntities);

        (new SchemaTool($this->entityManager))->createSchema(array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            [IndexedRoute::class, IndexedChannel::class, IndexedPage::class, IndexedPageTranslation::class],
        ));

        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @param array<int|string, mixed> $extraArguments arguments added after the four original ones (by position or by name)
     */
    protected function registerIndexer(array $extraArguments = []): EntityRouteIndexer
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->entityManager);

        $indexer = new EntityRouteIndexer(
            new ContentRepository($registry),
            new ParameterBag(['cmf_routing.dynamic.persistence.orm.route_class' => IndexedRoute::class]),
            $this->dispatcher,
            $this->entityManager,
            ...$extraArguments,
        );

        // one indexer at a time, like the service
        if (null !== $this->indexer) {
            $this->entityManager->getEventManager()->removeEventListener(self::listenedEvents(true === ($this->indexerArguments['indexOnFlush'] ?? false)), $this->indexer);
        }
        $this->entityManager->getEventManager()->addEventListener(self::listenedEvents(true === ($extraArguments['indexOnFlush'] ?? false)), $indexer);
        $this->indexer = $indexer;
        $this->indexerArguments = $extraArguments;

        return $indexer;
    }

    /**
     * Simulates a new process (request, command): empty identity map, new indexer with the same arguments. The legacy
     * listener indexes each entity once per process (and compares the entities already indexed loosely).
     */
    protected function restartProcess(): EntityRouteIndexer
    {
        $this->entityManager->clear();

        return $this->registerIndexer($this->indexerArguments);
    }

    /**
     * Registers the indexer ("index_on_flush") and Gedmo's sluggable listener in the order Symfony gives them for
     * onFlush: the priority of the indexer's onFlush tag (set by SyliusHappyCMSExtension) against the one of
     * stof_doctrine_extensions (none, so 0).
     */
    protected function registerIndexerAndSluggableListenerInServiceOrder(): EntityRouteIndexer
    {
        $sluggable = new SluggableListener();
        $sluggable->setAnnotationReader(new AttributeReader());

        // highest priority first; same priority: registration order (the plugin's services come first)
        if (SyliusHappyCMSExtension::ROUTE_INDEXER_ON_FLUSH_PRIORITY < 0) {
            $this->entityManager->getEventManager()->addEventSubscriber($sluggable);

            return $this->registerIndexer(['indexOnFlush' => true]);
        }
        $indexer = $this->registerIndexer(['indexOnFlush' => true]);
        $this->entityManager->getEventManager()->addEventSubscriber($sluggable);

        return $indexer;
    }

    /**
     * The events of the tags of the indexer service (see SyliusHappyCMSExtension).
     *
     * @return string[]
     */
    protected static function listenedEvents(bool $indexOnFlush): array
    {
        return $indexOnFlush ? [Events::onFlush, Events::postPersist] : [Events::postPersist, Events::postUpdate];
    }

    protected function countFlushes(): object
    {
        $counter = new class() {
            public int $count = 0;

            public function postFlush(): void
            {
                ++$this->count;
            }
        };
        $this->entityManager->getEventManager()->addEventListener([Events::postFlush], $counter);

        return $counter;
    }

    protected function savePage(string $slug, ?string $category = null, string $locale = 'en_US', bool $homepage = false): IndexedPage
    {
        $page = new IndexedPage($slug, $locale);
        $page->category = $category;
        $page->isHomePage = $homepage;
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $page;
    }

    /**
     * @return array<string, string> route name => static prefix, as stored
     */
    protected function storedPrefixes(): array
    {
        $prefixes = [];
        foreach ($this->entityManager->getConnection()->fetchAllAssociative('SELECT name, staticPrefix FROM indexed_route ORDER BY id') as $row) {
            $prefixes[(string) $row['name']] = (string) $row['staticPrefix'];
        }

        return $prefixes;
    }
}
