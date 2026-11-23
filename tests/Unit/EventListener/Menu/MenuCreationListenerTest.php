<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem as PluginMenuItem;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuCreationListener;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\MenuItem;

/**
 * A new menu receives a root item. Its class comes from the `sylius_happy_cms.menu_item` resource, or else from the
 * `sylius_happy_cms.menu.menu_item.menu_item_model` setting.
 */
final class MenuCreationListenerTest extends TestCase
{
    public function testTheRootItemUsesTheConfiguredModelWhenNoMenuItemResourceIsDeclared(): void
    {
        $menu = new Menu();

        $this->listener([
            'sylius.resources' => ['app.menu_link' => ['classes' => ['model' => MenuItem::class]]],
            'sylius_happy_cms.menu.menu_item.menu_item_model' => MenuItem::class,
        ])->prePersist($menu);

        $rootItem = $menu->getRootItem();
        self::assertInstanceOf(MenuItem::class, $rootItem);
        self::assertSame('Root', $rootItem->getTranslation('fr_FR')->getName());
    }

    public function testNoRootItemWhenTheConfiguredModelIsThePluginMappedSuperclass(): void
    {
        $menu = new Menu();

        $this->listener([
            'sylius.resources' => [],
            'sylius_happy_cms.menu.menu_item.menu_item_model' => PluginMenuItem::class,
        ])->prePersist($menu);

        self::assertNull($menu->getRootItem());
    }

    public function testTheMenuItemResourceStillComesFirst(): void
    {
        $menu = new Menu();

        $this->listener([
            'sylius.resources' => ['sylius_happy_cms.menu_item' => ['classes' => ['model' => MenuItem::class]]],
            'sylius_happy_cms.menu.menu_item.menu_item_model' => PluginMenuItem::class,
        ])->prePersist($menu);

        self::assertInstanceOf(MenuItem::class, $menu->getRootItem());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function listener(array $parameters): MenuCreationListener
    {
        $localeProvider = $this->createStub(LocaleProviderInterface::class);
        $localeProvider->method('getAvailableLocalesCodes')->willReturn(['fr_FR']);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Root');

        $listener = new MenuCreationListener($localeProvider, $translator, new ParameterBag($parameters));

        // A PHP warning (undefined array key) is a failure here: debug and test environments turn it into an exception.
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });

        return $listener;
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }
}
