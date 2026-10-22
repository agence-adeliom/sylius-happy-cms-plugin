<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Cmf\Component\Routing\RedirectRouteInterface;
use Symfony\Cmf\Component\Routing\RouteReferrersInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('cmf_routing.dynamic.persistence.orm.route_class', 'App\\Entity\\HappyCMS\\Cmf\\Route');

    $container->extension('cmf_routing', [
        'chain' => [
            'routers_by_id' => [
                'router.default' => 100,
                'cmf_routing.dynamic_router' => 50,
            ],
        ],
        'dynamic' => [
            'persistence' => [
                'orm' => [
                    'enabled' => true,
                ],
            ],
            'locales' => [
                'en_US',
            ],
            'match_implicit_locale' => false,
            'controllers_by_class' => [
                RedirectRouteInterface::class => 'cmf_routing.redirect_controller:redirectAction',
                RouteReferrersInterface::class => 'sylius.happy_cms.plugin.controller.render::renderAction',
                CmsRoutableInterface::class => 'sylius.happy_cms.plugin.controller.render::renderAction',
            ],
        ],
    ]);
};
