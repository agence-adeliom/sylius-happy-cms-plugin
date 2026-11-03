<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\ConfigRepositoryPass;
use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\OtherProjectConfig;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\ProjectConfig;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler\Fixtures\ProjectConfigRepository;

final class ConfigRepositoryPassTest extends TestCase
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
        (new ConfigRepositoryPass())->process($container);
    }

    /**
     * @param array<string, array<string, mixed>> $resources
     */
    private function container(string $configModel, array $resources): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_happy_cms.config.config_model', $configModel);
        $container->setParameter('sylius.resources', $resources);

        return $container;
    }
}
