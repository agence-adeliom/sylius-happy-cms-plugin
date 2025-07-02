<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface;

class RouteAdmin extends AbstractRouteAdmin
{
    public static function getName(): string
    {
        return 'sylius_happy_cms_route_admin';
    }

    public static function getEntityFqcn(): string
    {
        return RouteInterface::class;
    }
}
