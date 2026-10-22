<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class SeoTemplatesConfigurationTest extends TestCase
{
    public function testTheSeoTemplatesDefaultToThePluginTemplates(): void
    {
        $container = $this->load([]);

        self::assertSame([
            'metas' => SeoExtension::DEFAULT_METAS_TEMPLATE,
            'breadcrumb' => SeoExtension::DEFAULT_BREADCRUMB_TEMPLATE,
        ], $container->getParameter('sylius_happy_cms.seo.templates'));
        self::assertSame('%sylius_happy_cms.seo.templates%', $container->getDefinition(SeoExtension::class)->getArgument(5));
    }

    public function testTheSeoTemplatesCanBeReplaced(): void
    {
        $container = $this->load(['seo' => ['templates' => ['metas' => 'seo/metas.html.twig']]]);

        self::assertSame([
            'metas' => 'seo/metas.html.twig',
            'breadcrumb' => SeoExtension::DEFAULT_BREADCRUMB_TEMPLATE,
        ], $container->getParameter('sylius_happy_cms.seo.templates'));
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
