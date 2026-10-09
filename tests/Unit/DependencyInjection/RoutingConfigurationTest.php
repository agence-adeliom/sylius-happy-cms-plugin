<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\EventListener\TrailingSlashRedirectListener;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteParentPathResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class RoutingConfigurationTest extends TestCase
{
    private const INDEXER = 'sylius.happy_cms.plugin.listener.entity_route_indexer';

    public function testTheRoutesKeepTheLocalePrefixByDefault(): void
    {
        $container = $this->load([]);

        self::assertSame(EntityRouteIndexer::DEFAULT_URL_PATTERN, $container->getParameter('sylius_happy_cms.routing.url_pattern'));
        self::assertSame('%sylius_happy_cms.routing.url_pattern%', $container->getDefinition(self::INDEXER)->getArgument(4));
    }

    public function testTheRoutesHaveNoTrailingSlashByDefault(): void
    {
        $container = $this->load([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.routing.trailing_slash'));
        self::assertSame('%sylius_happy_cms.routing.trailing_slash%', $container->getDefinition(self::INDEXER)->getArgument(5));
        self::assertFalse($container->hasDefinition(TrailingSlashRedirectListener::class));
    }

    public function testTheTrailingSlashOptionRegistersTheRedirectListener(): void
    {
        $container = $this->load(['routing' => ['trailing_slash' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.routing.trailing_slash'));
        self::assertTrue($container->getDefinition(TrailingSlashRedirectListener::class)->hasTag('kernel.event_listener'));
    }

    public function testParentPathResolversAreAutoconfiguredAndInjected(): void
    {
        $container = $this->load([]);

        self::assertArrayHasKey(RouteParentPathResolverInterface::class, $container->getAutoconfiguredInstanceof());
        self::assertTrue($container->getAutoconfiguredInstanceof()[RouteParentPathResolverInterface::class]->hasTag(RouteParentPathResolverInterface::TAG));
        $resolvers = $container->getDefinition(self::INDEXER)->getArgument(6);
        self::assertInstanceOf(TaggedIteratorArgument::class, $resolvers);
        self::assertSame(RouteParentPathResolverInterface::TAG, $resolvers->getTag());
    }

    public function testTheUrlPatternCanBeChanged(): void
    {
        $container = $this->load(['routing' => ['url_pattern' => '{{other_entity_path}}{{current_entity_path}}']]);

        self::assertSame('{{other_entity_path}}{{current_entity_path}}', $container->getParameter('sylius_happy_cms.routing.url_pattern'));
    }

    public function testTheUrlPatternMustContainTheEntityPath(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load(['routing' => ['url_pattern' => '/{{locale}}']]);
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
