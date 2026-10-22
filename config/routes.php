<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Imports the routes of the plugin, with the admin routes under the Sylius admin path (`/admin` by default, see the
 * SYLIUS_ADMIN_ROUTING_PATH_NAME environment variable).
 *
 * To choose the prefix or the host yourself, import `@SyliusHappyCMSPlugin/config/routes/admin.php`,
 * `@SyliusHappyCMSPlugin/config/routes/media_admin.php` and `@SyliusHappyCMSPlugin/config/routes/media_public.php`
 * instead.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->import('routes/admin.php')
        ->prefix('/%sylius_admin.path_name%');

    // Media manager routes (`media.*`), under the Sylius admin path too.
    $routes->import('routes/media_admin.php')
        ->prefix('/%sylius_admin.path_name%');

    // Public download route (`media.file_download`, /media/download/{path}), not under the admin path.
    $routes->import('routes/media_public.php');
};
