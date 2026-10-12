<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page as BasePage;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine\MappedSuperclassAssociationDeclaringClassListener;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\EventListener\ORMMappedSuperClassSubscriber;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Metadata\Registry;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel\InheritedPage;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel\LandingPage;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel\ModelChannel;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel\ModelRoute;

/**
 * A project page extending the plugin page and subclassed with single table inheritance, loaded the way a Sylius
 * application loads it: Sylius ORMMappedSuperClassSubscriber + resolve_target_entities (SQLite in memory).
 */
final class MappedSuperclassAssociationDeclaringClassListenerTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $attributes = new AttributeDriver([__DIR__ . '/Fixtures/PageModel']);
        $driver = new MappingDriverChain();
        $driver->addDriver($attributes, 'Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Doctrine\Fixtures\PageModel');
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity');
        $config->setMetadataDriverImpl($driver);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(PageInterface::class, InheritedPage::class, []);
        $resolveTargetEntities->addResolveTargetEntity(RouteInterface::class, ModelRoute::class, []);
        $resolveTargetEntities->addResolveTargetEntity(ChannelInterface::class, ModelChannel::class, []);

        $eventManager = $this->entityManager->getEventManager();
        $eventManager->addEventListener([Events::loadClassMetadata], new ORMMappedSuperClassSubscriber(new Registry()));
        $eventManager->addEventSubscriber($resolveTargetEntities);
    }

    public function testASingleTableSubclassOfTheProjectPageCannotBeLoadedWithoutTheListener(): void
    {
        $this->expectExceptionMessage('Property "routes" in "' . LandingPage::class . '" was already declared');

        $this->entityManager->getClassMetadata(LandingPage::class);
    }

    public function testASingleTableSubclassOfTheProjectPageInheritsTheCollectionsOfThePluginPage(): void
    {
        $this->registerListener();

        $metadata = $this->entityManager->getClassMetadata(LandingPage::class);

        foreach (['routes', 'children', 'parent'] as $field) {
            self::assertSame(BasePage::class, $metadata->getAssociationMapping($field)->declared, $field);
            self::assertSame(InheritedPage::class, $metadata->getAssociationMapping($field)->inherited, $field);
        }
        self::assertSame(ModelRoute::class, $metadata->getAssociationMapping('routes')->targetEntity);
        self::assertSame(InheritedPage::class, $metadata->getAssociationMapping('children')->targetEntity);
    }

    public function testTheTreeAndTheRoutesOfASubclassArePersisted(): void
    {
        $this->registerListener();
        (new SchemaTool($this->entityManager))->createSchema(array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            [ModelRoute::class, ModelChannel::class, InheritedPage::class, LandingPage::class],
        ));

        $parent = new LandingPage();
        $child = new LandingPage();
        $child->setParent($parent);
        $route = new ModelRoute();
        $this->entityManager->persist($route);
        $parent->getRoutes()->add($route);
        foreach ([$parent, $child] as $page) {
            $page->setLft(0);
            $page->setLvl(0);
            $page->setRgt(0);
            $this->entityManager->persist($page);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(LandingPage::class, $parent->getId());
        self::assertInstanceOf(LandingPage::class, $reloaded);
        self::assertCount(1, $reloaded->getChildren());
        self::assertSame($child->getId(), $reloaded->getChildren()->first()->getId());
        self::assertCount(1, $reloaded->getRoutes());
    }

    public function testTheProjectPageItselfKeepsItsTable(): void
    {
        $this->registerListener();

        $metadata = $this->entityManager->getClassMetadata(InheritedPage::class);

        self::assertSame('model_page', $metadata->getTableName());
        self::assertNull($metadata->getAssociationMapping('routes')->inherited);
        self::assertSame(InheritedPage::class, $metadata->getAssociationMapping('routes')->sourceEntity);
    }

    private function registerListener(): void
    {
        $this->entityManager->getEventManager()->addEventListener(
            [Events::loadClassMetadata],
            new MappedSuperclassAssociationDeclaringClassListener(),
        );
    }
}
