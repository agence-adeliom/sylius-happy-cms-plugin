<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\ConfigRepositoryPass;
use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class ConfigRepositoryPassTest extends TestCase
{
    public function testTheAliasPointsToTheRepositoryOfTheResourceHoldingTheConfigModel(): void
    {
        $container = $this->container('App\Entity\Config', [
            'app.page' => ['classes' => ['model' => 'App\Entity\Page']],
            'app.config' => ['classes' => ['model' => 'App\Entity\Config']],
        ]);

        (new ConfigRepositoryPass())->process($container);

        self::assertSame('app.repository.config', (string) $container->getAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY));
    }

    public function testTheResourceNameOfThePluginTestApplicationStillWorks(): void
    {
        $container = $this->container('App\Entity\Config', [
            'sylius_happy_cms.config' => ['classes' => ['model' => 'App\Entity\Config']],
        ]);

        (new ConfigRepositoryPass())->process($container);

        self::assertSame('sylius_happy_cms.repository.config', (string) $container->getAlias(SyliusHappyCMSExtension::CONFIG_REPOSITORY));
    }

    public function testAMissingConfigResourceIsReported(): void
    {
        $container = $this->container('App\Entity\Config', [
            'app.page' => ['classes' => ['model' => 'App\Entity\Page']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('App\Entity\Config');

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
