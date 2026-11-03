<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\ResourceRepositoryPass;
use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\OtherProjectConfig;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\ProjectConfig;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\ProjectConfigRepository;

final class ResourceRepositoryPassTest extends TestCase
{
    public function testTheSyliusRepositoryOfTheResourceIsUsedWhenItIsAConfigRepository(): void
    {
        // Plugin test application: resource `sylius_happy_cms.config` with a config repository.
        $container = $this->container(ProjectConfig::class, [
            'app.page' => ['classes' => ['model' => 'App\Entity\Page']],
            'sylius_happy_cms.config' => ['classes' => ['model' => ProjectConfig::class]],
        ]);
        $container->setDefinition('sylius_happy_cms.repository.config', new Definition(ProjectConfigRepository::class));

        $this->process($container);

        self::assertSame('sylius_happy_cms.repository.config', (string) $container->getAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY));
    }

    public function testASubclassResourceWithTheGenericSyliusRepositoryUsesTheRepositoryOfTheEntity(): void
    {
        // Tryba: `config_model` left to the plugin default, resource `app.config` on a subclass,
        // with the generic Sylius repository; the real repository is the one of the entity mapping.
        $container = $this->container(Config::class, [
            'app.config' => ['classes' => ['model' => ProjectConfig::class, 'repository' => EntityRepository::class]],
        ]);
        $container->setDefinition('app.repository.config', new Definition(EntityRepository::class));
        $container->setDefinition(ProjectConfigRepository::class, new Definition(ProjectConfigRepository::class));

        $this->process($container);

        self::assertSame(ProjectConfigRepository::class, (string) $container->getAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY));
    }

    public function testTheDoctrineRepositoryOfTheResourceModelIsTheLastResort(): void
    {
        $container = $this->container(Config::class, [
            'app.config' => ['classes' => ['model' => ProjectConfig::class, 'repository' => EntityRepository::class]],
        ]);
        $container->setDefinition('app.repository.config', new Definition(EntityRepository::class));

        $this->process($container);

        $this->assertDoctrineRepositoryOf(ProjectConfig::class, $container);
    }

    public function testAnExactModelMatchWinsOverSubclasses(): void
    {
        $container = $this->container(ProjectConfig::class, [
            'app.other_config' => ['classes' => ['model' => OtherProjectConfig::class]],
            'app.config' => ['classes' => ['model' => ProjectConfig::class]],
        ]);
        $container->setDefinition('app.repository.config', new Definition(ConfigRepository::class));

        $this->process($container);

        self::assertSame('app.repository.config', (string) $container->getAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY));
    }

    public function testSeveralSubclassResourcesAreReportedAsAmbiguous(): void
    {
        $container = $this->container(Config::class, [
            'app.config' => ['classes' => ['model' => ProjectConfig::class]],
            'app.other_config' => ['classes' => ['model' => OtherProjectConfig::class]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"app.config", "app.other_config"');

        $this->process($container);
    }

    public function testWithoutResourceTheContainerStillBuildsOnTheDoctrineRepositoryOfTheConfigModel(): void
    {
        $container = $this->container(Config::class, [
            'app.page' => ['classes' => ['model' => 'App\Entity\Page']],
        ]);

        $this->process($container);

        $this->assertDoctrineRepositoryOf(Config::class, $container);
    }

    public function testThePageRepositoryInterfaceIsAliasedToTheRepositoryOfThePageResource(): void
    {
        $container = $this->container(Page::class, [
            'app.page' => ['classes' => ['model' => Page::class]],
        ], 'sylius_happy_cms.page.page_model');
        $container->setDefinition('app.repository.page', new Definition(PageRepository::class));

        ResourceRepositoryPass::page()->process($container);

        self::assertSame('app.repository.page', (string) $container->getAlias(PageRepositoryInterface::class));
    }

    public function testTheRouteRepositoryInterfaceIsAliasedToTheRepositoryOfTheRouteResource(): void
    {
        $container = $this->container(Route::class, [
            'sylius_happy_cms.route' => ['classes' => ['model' => Route::class]],
            'sylius_happy_cms.redirect_route' => ['classes' => ['model' => RedirectRoute::class]],
        ], 'sylius_happy_cms.route.route_model');
        $container->setDefinition('sylius_happy_cms.repository.route', new Definition(RouteRepository::class));

        ResourceRepositoryPass::route()->process($container);

        self::assertSame('sylius_happy_cms.repository.route', (string) $container->getAlias(RouteRepositoryInterface::class));
    }

    public function testALegacyIdDefinedByTheProjectIsKept(): void
    {
        // The plugin aliased PageRepositoryInterface to this id without defining it: a project may have.
        $container = $this->container(Page::class, [
            'app.page' => ['classes' => ['model' => Page::class]],
        ], 'sylius_happy_cms.page.page_model');
        $container->setDefinition('app.repository.page', new Definition(PageRepository::class));
        $container->setDefinition('happy_cms_page.page.repository', new Definition(PageRepository::class));

        ResourceRepositoryPass::page()->process($container);

        self::assertSame('happy_cms_page.page.repository', (string) $container->getAlias(PageRepositoryInterface::class));
    }

    public function testSeveralSubclassResourcesLeaveAnOptionalRepositoryUndefined(): void
    {
        $container = $this->container(Config::class, [
            'app.config' => ['classes' => ['model' => ProjectConfig::class]],
            'app.other_config' => ['classes' => ['model' => OtherProjectConfig::class]],
        ]);

        (new ResourceRepositoryPass('sylius_happy_cms.config.config_model', ConfigRepositoryInterface::class, 'optional.repository'))->process($container);

        self::assertFalse($container->has('optional.repository'));
    }

    private function assertDoctrineRepositoryOf(string $model, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(SyliusHappyCMSExtension::CONFIG_REPOSITORY);
        $factory = $definition->getFactory();
        self::assertIsArray($factory);
        self::assertInstanceOf(Reference::class, $factory[0]);
        self::assertSame('doctrine', (string) $factory[0]);
        self::assertSame('getRepository', $factory[1]);
        self::assertSame([$model], $definition->getArguments());
    }

    private function process(ContainerBuilder $container): void
    {
        ResourceRepositoryPass::config()->process($container);
    }

    /**
     * @param array<string, array<string, mixed>> $resources
     */
    private function container(string $model, array $resources, string $modelParameter = 'sylius_happy_cms.config.config_model'): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter($modelParameter, $model);
        $container->setParameter('sylius.resources', $resources);

        return $container;
    }
}
