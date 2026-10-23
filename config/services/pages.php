<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageController;
use Adeliom\SyliusHappyCMSPlugin\Controller\PageBuilder\PageBuilderController;
use Adeliom\SyliusHappyCMSPlugin\Twig\Page\PageExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set('happy_cms.page.twig.extension', PageExtension::class)
        ->public()
        ->args([service('doctrine.orm.entity_manager')])
        ->tag('twig.extension');

    $services->set(PageController::class)
        ->public()
        ->args([
            service('sylius.happy_cms.plugin.services.render'),
            service('router'),
            service('translator'),
            service('doctrine.orm.entity_manager'),
        ]);

    $services->set(PageBuilderController::class)
        ->public()
        ->args([
            service('doctrine.orm.entity_manager'),
            service('parameter_bag'),
        ]);
};
