<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RedirectRouteAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RouteAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Config\ConfigAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuItemAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\SharedBlockAdmin;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RedirectRouteRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepository;
use Adeliom\SyliusHappyCMSPlugin\Repository\SharedBlock\SharedBlockRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylius_happy_cms', [
        // Use a specific template for page builder preview (example without header/footer)
        'page_builder' => [
            'preview_template' => '@SyliusHappyCMSPlugin/front/document/preview.html.twig',
        ],
        'page' => [
            'page_model' => Page::class,
            'page_repository' => PageRepository::class,
            'page_admin' => PageAdmin::class,
        ],
        'seo' => [
            'title' => [
                'suffix' => 'ACME',
            ],
        ],
        'config' => [
            'config_model' => Config::class,
            'config_repository' => ConfigRepository::class,
            'config_admin' => ConfigAdmin::class,
        ],
        'menu' => [
            'menu' => [
                'menu_model' => Menu::class,
                'menu_repository' => MenuRepository::class,
                'menu_admin' => MenuAdmin::class,
            ],
            'menu_item' => [
                'menu_item_model' => MenuItem::class,
                'menu_item_repository' => MenuItemRepository::class,
                'menu_item_admin' => MenuItemAdmin::class,
            ],
        ],
        'media' => [
            'storage_name' => 'uploads.storage',
            'base_url' => '/media/download',
            'media_entity' => Media::class,
            'folder_entity' => Folder::class,
            // File size limits (in MB, 0 = no limit)
            'max_file_size' => 10,                  // Maximum 10 MB per file
            'max_total_upload_size' => 50,          // Maximum 50 MB total per request
            'max_files_per_upload' => 20,           // Maximum 20 files per upload
            // Rate limiting for upload requests
            'rate_limiting' => [
                'enabled' => true,                  // Enable rate limiting
                'max_requests' => 60,               // 60 uploads per period
                'period' => 60,                     // Period in seconds (60 = 1 minute)
                'ban_duration' => 300,              // Ban for 5 minutes after exceeding limit
                'cache_pool' => 'cache.app',        // Cache pool for rate limit storage
            ],
        ],
        'shared_block' => [
            'shared_block_model' => SharedBlock::class,
            'shared_block_repository' => SharedBlockRepository::class,
            'shared_block_admin' => SharedBlockAdmin::class,
        ],
        'route' => [
            'route_model' => Route::class,
            'route_repository' => RouteRepository::class,
            'route_admin' => RouteAdmin::class,
        ],
        'redirect_route' => [
            'redirect_route_model' => RedirectRoute::class,
            'redirect_route_repository' => RedirectRouteRepository::class,
            'redirect_route_admin' => RedirectRouteAdmin::class,
        ],
    ]);
};
