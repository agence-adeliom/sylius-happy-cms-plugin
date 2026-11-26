<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class PageBuilderConfigurationTest extends TestCase
{
    public function testThePreviewUrlTtlDefaultsToFiveMinutes(): void
    {
        self::assertSame(300, $this->load([])->getParameter('sylius_happy_cms.page_builder.preview_url_ttl'));
    }

    public function testThePreviewUrlTtlCanBeChanged(): void
    {
        $container = $this->load(['page_builder' => ['preview_url_ttl' => 60]]);

        self::assertSame(60, $container->getParameter('sylius_happy_cms.page_builder.preview_url_ttl'));
    }

    public function testThePreviewUrlTtlMustBePositive(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['page_builder' => ['preview_url_ttl' => 0]]);
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
