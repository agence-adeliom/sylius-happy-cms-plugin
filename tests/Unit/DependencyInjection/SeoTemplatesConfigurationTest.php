<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

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
        $plugin = new ContainerBuilder();
        $plugin->registerExtension(new SyliusHappyCMSExtension());
        (new PhpFileLoader($plugin, new FileLocator(__DIR__ . '/../../../config/packages')))->load('sylius_happy_cms.php');
        $pluginConfig = $plugin->getExtensionConfig('sylius_happy_cms')[0];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        (new SyliusHappyCMSExtension())->load([$pluginConfig, $projectConfig], $container);

        return $container;
    }
}
