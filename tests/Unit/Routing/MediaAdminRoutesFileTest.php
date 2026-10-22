<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\PhpFileLoader;

final class MediaAdminRoutesFileTest extends TestCase
{
    /**
     * Every media manager route follows the `sylius_happy_cms.media.admin_path` option.
     */
    public function testEveryMediaManagerRouteIsUnderTheConfiguredPath(): void
    {
        $routes = (new PhpFileLoader(new FileLocator(\dirname(__DIR__, 3) . '/config/routes')))->load('media_admin.php');

        self::assertCount(18, $routes);
        foreach ($routes as $name => $route) {
            self::assertStringStartsWith('media.', $name);
            self::assertStringStartsWith('/%sylius_happy_cms.media.admin_path%/', $route->getPath(), $name);
        }
    }
}
