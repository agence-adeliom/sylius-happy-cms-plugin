<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuCreationListener;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuRootItemOnDeleteListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
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
        self::assertCount(1, $this->deprecationsOf('menu.root_item_on_delete_set_null'));
        self::assertStringStartsWith(
            'Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Setting the "sylius_happy_cms.menu.root_item_on_delete_set_null" option to false is deprecated.',
            $this->deprecationsOf('menu.root_item_on_delete_set_null')[0],
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
        self::assertSame([], $this->deprecationsOf('menu.root_item_on_delete_set_null'));
    }

    public function testTheRootItemIsNotAssignedWithADeprecationByDefault(): void
    {
        $container = $this->load([]);

        self::assertNull($container->getParameter('sylius_happy_cms.menu.root_item.assign'));
        $listener = $container->getDefinition(MenuCreationListener::class);
        self::assertFalse($listener->getArgument(3));
        self::assertEquals(new Reference('doctrine'), $listener->getArgument(4));
        self::assertTrue($listener->hasTag('doctrine.orm.entity_listener'));
        self::assertSame(
            ['Since agence-adeliom/sylius-happy-cms-plugin 2.3.3: Not setting the "sylius_happy_cms.menu.root_item.assign" option is deprecated. It will be removed in 3.0, where assigning the root item created with a menu to Menu#rootItem is the only behaviour. Set it to true to opt in now (see UPGRADE.md).'],
            $this->deprecationsOf('menu.root_item.assign'),
        );
    }

    public function testSettingTheRootItemAssignOptionToFalseIsDeprecated(): void
    {
        $container = $this->load(['menu' => ['root_item' => ['assign' => false]]]);

        self::assertFalse($container->getDefinition(MenuCreationListener::class)->getArgument(3));
        self::assertCount(1, $this->deprecationsOf('menu.root_item.assign'));
        self::assertStringContainsString('option to false is deprecated', $this->deprecationsOf('menu.root_item.assign')[0]);
    }

    public function testTheRootItemIsAssignedWhenOptedIn(): void
    {
        $container = $this->load(['menu' => ['root_item' => ['assign' => true]]]);

        self::assertTrue($container->getParameter('sylius_happy_cms.menu.root_item.assign'));
        self::assertTrue($container->getDefinition(MenuCreationListener::class)->getArgument(3));
        self::assertSame([], $this->deprecationsOf('menu.root_item.assign'));
    }

    /**
     * @return list<string>
     */
    private function deprecationsOf(string $path): array
    {
        return array_values(array_filter(
            $this->deprecations,
            static fn (string $message): bool => str_contains($message, '"sylius_happy_cms.' . $path . '"'),
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
