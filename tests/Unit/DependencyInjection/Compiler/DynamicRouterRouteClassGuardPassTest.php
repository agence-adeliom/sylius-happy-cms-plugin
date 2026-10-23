<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Compiler;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\DynamicRouterRouteClassGuardPass;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route as BaseRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf\Route;

final class DynamicRouterRouteClassGuardPassTest extends TestCase
{
    public function testTheDynamicRouterIsLeftOutWhileTheRouteModelIsNotConfigured(): void
    {
        $container = $this->createContainer(BaseRoute::class);

        (new DynamicRouterRouteClassGuardPass())->process($container);

        self::assertSame(['router.default'], $this->chainedRouters($container));
    }

    public function testTheDynamicRouterIsKeptWithTheRouteEntity(): void
    {
        $container = $this->createContainer(Route::class);

        (new DynamicRouterRouteClassGuardPass())->process($container);

        self::assertSame(['router.default', 'cmf_routing.dynamic_router'], $this->chainedRouters($container));
    }

    public function testTheRouteClassParameterIsResolved(): void
    {
        $container = $this->createContainer('%app.route_class%');
        $container->setParameter('app.route_class', BaseRoute::class);

        (new DynamicRouterRouteClassGuardPass())->process($container);

        self::assertSame(['router.default'], $this->chainedRouters($container));
    }

    public function testAMissingRouteClassFailsTheContainerBuild(): void
    {
        $container = $this->createContainer('App\Entity\HappyCMS\Cmf\MissingRoute');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The CMF route class "App\Entity\HappyCMS\Cmf\MissingRoute"');

        (new DynamicRouterRouteClassGuardPass())->process($container);
    }

    public function testNothingHappensWithoutTheChainRouter(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter(DynamicRouterRouteClassGuardPass::ROUTE_CLASS_PARAMETER, 'App\Missing');

        (new DynamicRouterRouteClassGuardPass())->process($container);

        self::assertFalse($container->hasDefinition(DynamicRouterRouteClassGuardPass::CHAIN_ROUTER));
    }

    private function createContainer(string $routeClass): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter(DynamicRouterRouteClassGuardPass::ROUTE_CLASS_PARAMETER, $routeClass);
        $container->setDefinition(DynamicRouterRouteClassGuardPass::CHAIN_ROUTER, (new Definition())
            ->addMethodCall('add', [new Reference('router.default'), '100'])
            ->addMethodCall('add', [new Reference('cmf_routing.dynamic_router'), '50']));

        return $container;
    }

    /**
     * @return list<string>
     */
    private function chainedRouters(ContainerBuilder $container): array
    {
        return array_map(
            static fn (array $call): string => (string) $call[1][0],
            $container->getDefinition(DynamicRouterRouteClassGuardPass::CHAIN_ROUTER)->getMethodCalls(),
        );
    }
}
