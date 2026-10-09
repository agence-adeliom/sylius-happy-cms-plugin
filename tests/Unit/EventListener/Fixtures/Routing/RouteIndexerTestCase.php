<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
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
        $driver->addDriver(new SimplifiedXmlDriver([$vendor . '/symfony-cmf/routing-bundle/src/Resources/config/doctrine-base' => 'Symfony\Component\Routing']), 'Symfony\Component\Routing');
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
     * @param array<int, mixed> $extraArguments arguments added after the four original ones
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

        $this->entityManager->getEventManager()->addEventListener(self::listenedEvents(), $indexer);

        return $indexer;
    }

    /**
     * @return string[]
     */
    protected static function listenedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate];
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
