<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuRootItemOnDeleteListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class MenuRootItemConfigurationTest extends TestCase
{
    /** @var list<string> */
    private array $deprecations = [];

    public function testTheRootItemKeyCascadesWithADeprecationByDefault(): void
    {
        $container = $this->load([]);

        self::assertNull($container->getParameter('sylius_happy_cms.menu.root_item_on_delete_set_null'));
        self::assertFalse($container->getDefinition(MenuRootItemOnDeleteListener::class)->hasTag('doctrine.event_listener'));
        self::assertContains(
            'Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Not setting the "sylius_happy_cms.menu.root_item_on_delete_set_null" option is deprecated. It will be removed in 3.0, where ON DELETE SET NULL on the root_item_id key of the menus is the only behaviour. Set it to true to opt in now (see UPGRADE.md).',
            $this->deprecations,
        );
    }

    public function testSettingTheRootItemKeyOptionToFalseIsDeprecated(): void
    {
        $container = $this->load(['menu' => ['root_item_on_delete_set_null' => false]]);

        self::assertFalse($container->getDefinition(MenuRootItemOnDeleteListener::class)->hasTag('doctrine.event_listener'));
        self::assertCount(1, $this->menuDeprecations());
        self::assertStringStartsWith(
            'Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Setting the "sylius_happy_cms.menu.root_item_on_delete_set_null" option to false is deprecated.',
            $this->menuDeprecations()[0],
        );
    }

    public function testTheRootItemKeyIsSetNullOnDeleteWhenOptedIn(): void
    {
        $container = $this->load(['menu' => ['root_item_on_delete_set_null' => true]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.menu.root_item_on_delete_set_null'));
        self::assertSame(
            [['event' => 'loadClassMetadata']],
            $container->getDefinition(MenuRootItemOnDeleteListener::class)->getTag('doctrine.event_listener'),
        );
        self::assertSame([], $this->menuDeprecations());
    }

    /**
     * @return list<string>
     */
    private function menuDeprecations(): array
    {
        return array_values(array_filter(
            $this->deprecations,
            static fn (string $message): bool => str_contains($message, '"sylius_happy_cms.menu.root_item_on_delete_set_null"'),
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

        set_error_handler(function (int $level, string $message): bool {
            $this->deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new SyliusHappyCMSExtension())->load([$pluginConfig['sylius_happy_cms'], $projectConfig], $container);
        } finally {
            restore_error_handler();
        }

        return $container;
    }
}
