<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Integration\Routing\Fixtures\AttributeRoutedController;

final class MediaRoutesTest extends KernelTestCase
{
    /**
     * A project importing its controllers with `type: attribute` gets its own routes,
     * not the plugin media routes in their place.
     */
    public function testAttributeRouteImportsLoadTheProjectRoutes(): void
    {
        self::bootKernel();

        $loader = self::getContainer()->get('routing.loader');
        self::assertInstanceOf(LoaderInterface::class, $loader);

        $file = (new \ReflectionClass(AttributeRoutedController::class))->getFileName();
        self::assertIsString($file);

        $routes = $loader->load($file, 'attribute');

        self::assertNotNull($routes->get('test_attribute_routed'));
        self::assertNull($routes->get('media.index'));
    }

    /**
     * The media routes are only registered through an explicit import of
     * `@SyliusHappyCMSPlugin/config/media/routes.php` and `public_routes.php` (done by the test application).
     */
    public function testMediaRoutesAreRegisteredByTheExplicitImport(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $routes = $router->getRouteCollection();

        self::assertSame('/admin/happy-cms-media/medias/', $routes->get('media.index')?->getPath());
        self::assertSame('/media/download/{path}', $routes->get('media.file_download')?->getPath());
    }
}
