<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminRoutesTest extends KernelTestCase
{
    /**
     * The admin and media manager routes imported through `@SyliusHappyCMSPlugin/config/routes.php`
     * follow the Sylius admin path instead of a hard-coded `/admin`.
     *
     * @param array<string, string> $parameters
     */
    #[DataProvider('adminRoutes')]
    public function testPluginRoutesAreServedUnderTheSyliusAdminPath(string $name, array $parameters, string $path): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $adminPath = $container->getParameter('sylius_admin.path_name');
        self::assertIsString($adminPath);

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertNotNull($router->getRouteCollection()->get($name), sprintf('Route "%s" is not defined.', $name));
        self::assertSame(sprintf('/%s%s', $adminPath, $path), $router->generate($name, $parameters));
    }

    /**
     * @return iterable<string, array{string, array<string, string>, string}>
     */
    public static function adminRoutes(): iterable
    {
        yield 'shared block select' => ['sylius_happy_cms_admin_shared_block_select', [], '/shared-blocks/select'];
        yield 'menu items' => ['sylius_happy_cms_admin_menu_item_index', [], '/menu-items/'];
        yield 'page cache' => ['sylius_happy_cms_admin_page_clear_cache', [], '/pages/clear-cache'];
        yield 'page builder' => ['sylius_happy_cms_admin_page_builder', ['resource' => 'page', 'id' => '1'], '/happy-cms-builder/page/1'];
        yield 'page builder block' => ['sylius_happy_cms_admin_page_builder_block_edit', ['resource' => 'page', 'id' => '1', 'blockId' => '2'], '/happy-cms-builder/page/1/block/2/edit'];
        yield 'media manager' => ['media.index', [], '/happy-cms-media/medias/'];
        yield 'media upload' => ['media.upload', [], '/happy-cms-media/medias/upload'];
    }
}
