<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuCreationListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class MenuRootItemConfigurationTest extends TestCase
{
    public function testTheRootItemIsCreatedWithTheTranslatedLabelByDefault(): void
    {
        $container = $this->load([]);

        self::assertTrue($container->getParameter('sylius_happy_cms.menu.root_item.auto_create'));
        self::assertSame(MenuCreationListener::DEFAULT_ROOT_ITEM_LABEL, $container->getParameter('sylius_happy_cms.menu.root_item.label'));

        $listener = $container->getDefinition(MenuCreationListener::class);
        self::assertTrue($listener->hasTag('doctrine.orm.entity_listener'));
        self::assertSame('%sylius_happy_cms.menu.root_item.label%', $listener->getArgument(3));
    }

    public function testTheRootItemLabelCanBeChanged(): void
    {
        $container = $this->load(['menu' => ['root_item' => ['label' => 'Root']]]);

        self::assertSame('Root', $container->getParameter('sylius_happy_cms.menu.root_item.label'));
    }

    public function testTheCreationOfTheRootItemCanBeDisabled(): void
    {
        $container = $this->load(['menu' => ['root_item' => ['auto_create' => false]]]);

        self::assertFalse($container->getParameter('sylius_happy_cms.menu.root_item.auto_create'));
        self::assertFalse($container->getDefinition(MenuCreationListener::class)->hasTag('doctrine.orm.entity_listener'));
    }

    public function testTheRootItemLabelCannotBeEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/menu\.root_item\.label/');

        $this->load(['menu' => ['root_item' => ['label' => '']]]);
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
