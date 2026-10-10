<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

final class BlockRenderConfigurationTest extends TestCase
{
    public function testTheBlockRenderOptionsAreDisabledByDefault(): void
    {
        $container = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.block_render.inherit_context'));
        self::assertFalse($container->getParameter('sylius_happy_cms.block_render.missing_published_means_published'));
        self::assertFalse($container->getParameter('sylius_happy_cms.block_render.swallow_render_errors'));
    }

    public function testTheBlockRenderOptionsReachTheBlockHelper(): void
    {
        $container = $this->load(['block_render' => [
            'inherit_context' => true,
            'missing_published_means_published' => true,
            'swallow_render_errors' => true,
        ]]);

        $arguments = $container->getDefinition(Helper::class)->getArguments();

        self::assertSame('%sylius_happy_cms.block_render.inherit_context%', $arguments['$inheritContext']);
        self::assertSame('%sylius_happy_cms.block_render.missing_published_means_published%', $arguments['$missingPublishedMeansPublished']);
        self::assertSame('%sylius_happy_cms.block_render.swallow_render_errors%', $arguments['$swallowRenderErrors']);
        self::assertTrue($container->getParameter('sylius_happy_cms.block_render.inherit_context'));
        self::assertTrue($container->getParameter('sylius_happy_cms.block_render.missing_published_means_published'));
        self::assertTrue($container->getParameter('sylius_happy_cms.block_render.swallow_render_errors'));

        $logger = $arguments['$logger'];
        self::assertInstanceOf(Reference::class, $logger);
        self::assertSame('logger', (string) $logger);
        self::assertSame(ContainerInterface::IGNORE_ON_INVALID_REFERENCE, $logger->getInvalidBehavior());
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
