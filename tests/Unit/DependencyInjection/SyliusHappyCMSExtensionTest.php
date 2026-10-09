<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class SyliusHappyCMSExtensionTest extends TestCase
{
    public function testTheNotFoundListenerAndTheSeoTwigExtensionAreEnabledByDefault(): void
    {
        $container = $this->load([]);

        self::assertTrue($container->hasDefinition(SyliusHappyCMSExtension::NOT_FOUND_LISTENER));
        self::assertTrue($container->getDefinition(SeoExtension::class)->hasTag('twig.extension'));
    }

    public function testTheNotFoundListenerCanBeDisabled(): void
    {
        $container = $this->load(['redirect_route' => ['enable_not_found_listener' => false]]);

        self::assertFalse($container->hasDefinition(SyliusHappyCMSExtension::NOT_FOUND_LISTENER));
    }

    public function testTheSeoTwigExtensionCanBeDisabled(): void
    {
        $container = $this->load(['seo' => ['enable_twig_extension' => false]]);

        self::assertTrue($container->hasDefinition(SeoExtension::class), 'The service stays available to other services.');
        self::assertFalse($container->getDefinition(SeoExtension::class)->hasTag('twig.extension'));
    }

    /**
     * @param array<string, mixed> $projectConfig
     */
    private function load(array $projectConfig): ContainerBuilder
    {
        $pluginConfig = Yaml::parseFile(__DIR__ . '/../../../config/packages/sylius_happy_cms.yaml');
        self::assertIsArray($pluginConfig);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms'], $projectConfig], $container);

        return $container;
    }
}
