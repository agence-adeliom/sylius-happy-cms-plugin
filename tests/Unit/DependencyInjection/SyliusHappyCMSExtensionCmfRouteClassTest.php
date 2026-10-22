<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route as BaseRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf\Route;

final class SyliusHappyCMSExtensionCmfRouteClassTest extends TestCase
{
    public function testTheRouteModelIsGivenToTheCmfDynamicRouter(): void
    {
        $container = $this->prepend([['route' => ['route_model' => Route::class]]]);

        self::assertSame(Route::class, $this->cmfRouteClass($container));
    }

    public function testTheLastRouteModelWins(): void
    {
        $container = $this->prepend([
            ['route' => ['route_model' => BaseRoute::class]],
            ['route' => ['route_model' => Route::class]],
        ]);

        self::assertSame(Route::class, $this->cmfRouteClass($container));
    }

    public function testTheBaseRouteIsGivenWithoutRouteModel(): void
    {
        $container = $this->prepend([['seo' => []]]);

        self::assertSame(BaseRoute::class, $this->cmfRouteClass($container));
    }

    /**
     * @param list<array<string, mixed>> $configs
     */
    private function prepend(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new SyliusHappyCMSExtension();
        $container->registerExtension($extension);
        foreach ($configs as $config) {
            $container->loadFromExtension('sylius_happy_cms', $config);
        }

        $extension->prepend($container);

        return $container;
    }

    private function cmfRouteClass(ContainerBuilder $container): mixed
    {
        return $container->getExtensionConfig('cmf_routing')[0]['dynamic']['persistence']['orm']['route_class'] ?? null;
    }
}
