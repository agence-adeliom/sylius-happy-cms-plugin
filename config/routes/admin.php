<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\MenuItem\MenuItemController;
use Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageController;
use Adeliom\SyliusHappyCMSPlugin\Controller\PageBuilder\PageBuilderController;
use Adeliom\SyliusHappyCMSPlugin\Controller\SharedBlock\SharedBlockController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Admin routes of the plugin, without any path prefix.
 *
 * The plugin `config/routes.php` imports this file under the Sylius admin path
 * (`/%sylius_admin.path_name%`). Import this file directly when your admin is
 * served from another prefix or from its own host, e.g.:
 *
 *     $routes->import('@SyliusHappyCMSPlugin/config/routes/admin.php')
 *         ->host('%admin_host%');
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('sylius_happy_cms_admin_shared_block_select', '/shared-blocks/select')
        ->controller(SharedBlockController::class . '::select')
        ->methods(['GET']);

    $routes->add('sylius_happy_cms_admin_menu_item_index', '/menu-items/')
        ->controller(MenuItemController::class . '::indexAction')
        ->methods(['GET'])
        ->defaults([
            'route' => 'sylius_happy_cms_admin_menu_index',
            'permanent' => true,
        ]);

    $routes->add('sylius_happy_cms_admin_page_clear_cache', '/pages/clear-cache')
        ->controller(PageController::class . '::clearCacheAction')
        ->methods(['GET'])
        ->defaults([
            '_sylius' => [
                'template' => '$template',
                'menu' => '$menu',
                'vars' => '$vars',
                'permission' => true,
            ],
        ]);

    $routes->add('sylius_happy_cms_admin_page_builder', '/happy-cms-builder/{resource}/{id}')
        ->controller(PageBuilderController::class . '::builderAction')
        ->methods(['GET', 'POST'])
        ->defaults([
            '_sylius' => [
                'template' => '@SyliusHappyCMSPlugin/admin/page_builder/index.html.twig',
                'permission' => false,
            ],
        ])
        ->requirements(['id' => '\d+']);

    $routes->add('sylius_happy_cms_admin_page_builder_block_edit', '/happy-cms-builder/{resource}/{id}/block/{blockId}/edit')
        ->controller(PageBuilderController::class . '::blockEditAction')
        ->methods(['GET', 'POST'])
        ->defaults([
            '_sylius' => [
                'template' => '@SyliusHappyCMSPlugin/admin/page_builder/block_edit.html.twig',
                'permission' => false,
            ],
        ])
        ->requirements([
            'id' => '\d+',
            'blockId' => '\d+',
        ]);
};
