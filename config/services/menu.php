<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\MenuItem\MenuItemController;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Menu\MenuCreationListener;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Twig\Menu\MenuExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('happy_cms_menu.menu.repository', MenuRepositoryInterface::class)
        ->args([service('doctrine.orm.entity_manager')])
        ->call('setConfig', [param('sylius_happy_cms.menu.cache')])
        ->tag('doctrine.repository_service');

    $services->set('happy_cms_menu.menu_item.repository', MenuItemRepositoryInterface::class)
        ->args([service('doctrine.orm.entity_manager')])
        ->call('setConfig', [param('sylius_happy_cms.menu.cache')])
        ->tag('doctrine.repository_service');

    $services->set(MenuCreationListener::class)
        ->args([
            service('sylius.provider.locale'),
            service('translator'),
            service('parameter_bag'),
        ])
        // these are the options required to define the entity listener
        ->tag('doctrine.orm.entity_listener', [
            'event' => 'prePersist',
            'entity' => Menu::class,
            'lazy' => true,
        ]);

    $services->set(MenuExtension::class)
        ->public()
        ->autowire()
        ->args([
            service('twig'),
            service('doctrine.orm.entity_manager'),
        ])
        ->tag('twig.extension');

    $services->set(MenuItemController::class)
        ->public()
        ->autowire()
        ->args([service('router')])
        ->tag('controller.service_arguments');
};
