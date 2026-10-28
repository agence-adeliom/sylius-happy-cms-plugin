<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\EventListener\TrailingSlashRedirectListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
        self::assertSame('%sylius_happy_cms.routing.trailing_slash%', $container->getDefinition(self::INDEXER)->getArgument(6));
        self::assertFalse($container->hasDefinition(TrailingSlashRedirectListener::class));
    }

    public function testTheTrailingSlashOptionRegistersTheRedirectListener(): void
    {
        $container = $this->load(['routing' => ['trailing_slash' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.routing.trailing_slash'));
        self::assertTrue($container->getDefinition(TrailingSlashRedirectListener::class)->hasTag('kernel.event_listener'));
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

    public function testRepeatedSlashesAreKeptAndDeprecatedByDefault(): void
    {
        [$container, $deprecations] = $this->loadCollectingDeprecations([]);

        self::assertFalse($container->getParameter('sylius_happy_cms.routing.collapse_slashes'));
        self::assertSame('%sylius_happy_cms.routing.collapse_slashes%', $container->getDefinition(self::INDEXER)->getArgument(5));
        self::assertContains('Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Not setting the "sylius_happy_cms.routing.collapse_slashes" option is deprecated. It will be removed in 3.0, where collapsing the repeated slashes of a computed route path is the only behaviour. Set it to true to opt in now (see UPGRADE.md).', $deprecations);
    }

    public function testKeepingRepeatedSlashesExplicitlyIsDeprecated(): void
    {
        [$container, $deprecations] = $this->loadCollectingDeprecations(['routing' => ['collapse_slashes' => false]]);

        self::assertFalse($container->getParameter('sylius_happy_cms.routing.collapse_slashes'));
        self::assertContains('Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Setting the "sylius_happy_cms.routing.collapse_slashes" option to false is deprecated. It will be removed in 3.0, where collapsing the repeated slashes of a computed route path is the only behaviour. Set it to true to opt in now (see UPGRADE.md).', $deprecations);
    }

    public function testCollapsingRepeatedSlashesIsNotDeprecated(): void
    {
        [$container, $deprecations] = $this->loadCollectingDeprecations(['routing' => ['collapse_slashes' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.routing.collapse_slashes'));
        self::assertSame([], self::deprecationsOf('collapse_slashes', $deprecations));
    }

    /**
     * @param array<string, mixed> $projectConfig
     *
     * @return array{ContainerBuilder, list<string>}
     */
    private function loadCollectingDeprecations(array $projectConfig): array
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $container = $this->load($projectConfig);
        } finally {
            restore_error_handler();
        }

        return [$container, $deprecations];
    }

    /**
     * @param list<string> $deprecations
     *
     * @return list<string>
     */
    private static function deprecationsOf(string $option, array $deprecations): array
    {
        return array_values(array_filter(
            $deprecations,
            static fn (string $message): bool => str_contains($message, '"sylius_happy_cms.routing.' . $option . '"'),
        ));
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
