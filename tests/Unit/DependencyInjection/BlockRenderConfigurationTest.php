<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

final class BlockRenderConfigurationTest extends TestCase
{
    public function testTheBlockRenderOptionsAreDisabledByDefault(): void
    {
        $container = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.block_render.inherit_context'));
        self::assertFalse($container->getParameter('sylius_happy_cms.block_render.missing_published_means_published'));
    }

    public function testInheritContextReachesTheBlockHelper(): void
    {
        $container = $this->load(['block_render' => ['inherit_context' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.block_render.inherit_context'));
        self::assertSame('%sylius_happy_cms.block_render.inherit_context%', $container->getDefinition(Helper::class)->getArgument('$inheritContext'));
    }

    public function testMissingPublishedMeansPublishedReachesTheBlockHelper(): void
    {
        $container = $this->load(['block_render' => ['missing_published_means_published' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.block_render.missing_published_means_published'));
        self::assertSame(
            '%sylius_happy_cms.block_render.missing_published_means_published%',
            $container->getDefinition(Helper::class)->getArgument('$missingPublishedMeansPublished'),
        );
    }

    public function testTheFirstBlockHelperArgumentsStayPositional(): void
    {
        $arguments = $this->load([])->getDefinition(Helper::class)->getArguments();

        self::assertSame(
            ['twig', 'event_dispatcher', 'sylius.happy_cms.block_collection', 'adeliom.sylius.asset.renderer'],
            array_map(static fn (mixed $argument): string => $argument instanceof Reference ? (string) $argument : '', [$arguments[0], $arguments[1], $arguments[2], $arguments[3]]),
        );
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
