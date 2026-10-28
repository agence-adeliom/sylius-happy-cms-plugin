<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\PageContentBlockMappingPass;
use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Configuration;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageContentBlock;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\PageModel\ModelChannel;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\PageModel\ModelRoute;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\PageModel\ProjectPage;

/**
 * A project that maps the plugin page models (attribute driver, as DoctrineBundle does for the plugin directory) but
 * whose page has no `contentBlocks` association, i.e. does not use the relational page builder.
 */
final class PageContentBlockMappingPassTest extends TestCase
{
    private const FIXTURES_NAMESPACE = 'Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\PageModel';

    public function testThePageContentBlockModelIsMappedByDefault(): void
    {
        $driver = $this->compileAttributeDriver(null);

        self::assertContains(PageContentBlock::class, $driver->getAllClassNames());
        self::assertArrayHasKey(PageContentBlock::class, $this->validateMapping($driver));
    }

    public function testThePageContentBlockModelIsNotMappedWhenDisabled(): void
    {
        $driver = $this->compileAttributeDriver(false);

        self::assertNotContains(PageContentBlock::class, $driver->getAllClassNames());
        self::assertContains(ProjectPage::class, $driver->getAllClassNames());
        self::assertSame([], $this->validateMapping($driver));
    }

    public function testThePageModelsStayReadAsParentClassesWhenDisabled(): void
    {
        $entityManager = $this->createEntityManager($this->compileAttributeDriver(false));

        $metadata = $entityManager->getClassMetadata(ProjectPage::class);

        self::assertTrue($metadata->hasAssociation('children'));
        self::assertTrue($metadata->hasField('template'));
    }

    public function testEveryEntityManagerIsConcerned(): void
    {
        $container = $this->createContainer(false);
        $container->setParameter('doctrine.entity_managers', [
            'default' => 'doctrine.orm.default_entity_manager',
            'cms' => 'doctrine.orm.cms_entity_manager',
        ]);
        $container->setDefinition('doctrine.orm.cms_attribute_metadata_driver', $this->createDriverDefinition());

        (new PageContentBlockMappingPass())->process($container);

        foreach (['default', 'cms'] as $name) {
            self::assertNotSame([], $container->getDefinition(sprintf('doctrine.orm.%s_attribute_metadata_driver', $name))->getMethodCalls(), $name);
        }
    }

    public function testNothingHappensWithoutDoctrine(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter(PageContentBlockMappingPass::PARAMETER, false);

        (new PageContentBlockMappingPass())->process($container);

        self::assertFalse($container->hasDefinition('doctrine.orm.default_attribute_metadata_driver'));
    }

    public function testTheOptionIsEnabledByDefault(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertTrue($config['page_builder']['map_page_content_block']);
    }

    private function compileAttributeDriver(?bool $mapPageContentBlock): AttributeDriver
    {
        $container = $this->createContainer($mapPageContentBlock);
        $container->addCompilerPass(new PageContentBlockMappingPass());
        $container->compile();

        $driver = $container->get('doctrine.orm.default_attribute_metadata_driver');
        self::assertInstanceOf(AttributeDriver::class, $driver);

        return $driver;
    }

    private function createContainer(?bool $mapPageContentBlock): ContainerBuilder
    {
        $container = new ContainerBuilder();
        if (null !== $mapPageContentBlock) {
            $container->setParameter(PageContentBlockMappingPass::PARAMETER, $mapPageContentBlock);
        }
        $container->setParameter('doctrine.entity_managers', ['default' => 'doctrine.orm.default_entity_manager']);
        $container->setDefinition('doctrine.orm.default_attribute_metadata_driver', $this->createDriverDefinition());

        return $container;
    }

    private function createDriverDefinition(): Definition
    {
        return (new Definition(AttributeDriver::class, [[
            __DIR__ . '/Fixtures/PageModel',
            dirname(__DIR__, 4) . '/src/Entity/Page',
        ]]))->setPublic(true);
    }

    /**
     * @return array<string, list<string>>
     */
    private function validateMapping(MappingDriver $driver): array
    {
        return (new SchemaValidator($this->createEntityManager($driver)))->validateMapping();
    }

    private function createEntityManager(MappingDriver $attributes): EntityManager
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $config = ORMSetup::createAttributeMetadataConfig([], true);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $driver = new MappingDriverChain();
        $driver->addDriver($attributes, self::FIXTURES_NAMESPACE);
        $driver->addDriver($attributes, 'Adeliom\SyliusHappyCMSPlugin\Entity\Page');
        $config->setMetadataDriverImpl($driver);

        $entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);

        $resolveTargetEntities = new ResolveTargetEntityListener();
        $resolveTargetEntities->addResolveTargetEntity(PageInterface::class, ProjectPage::class, []);
        $resolveTargetEntities->addResolveTargetEntity(RouteInterface::class, ModelRoute::class, []);
        $resolveTargetEntities->addResolveTargetEntity(ChannelInterface::class, ModelChannel::class, []);
        $entityManager->getEventManager()->addEventSubscriber($resolveTargetEntities);

        return $entityManager;
    }
}
