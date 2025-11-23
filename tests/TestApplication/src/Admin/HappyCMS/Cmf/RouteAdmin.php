<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RouteAdmin as BaseRouteAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf\Route;

class RouteAdmin extends BaseRouteAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_route_admin';
    }

    public static function getEntityFqcn(): string
    {
        return Route::class;
    }
}
