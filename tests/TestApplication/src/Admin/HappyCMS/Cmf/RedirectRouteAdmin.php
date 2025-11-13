<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RedirectRouteAdmin as BaseRedirectRouteAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf\RedirectRoute;

class RedirectRouteAdmin extends BaseRedirectRouteAdmin
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_redirect_route_admin';
    }

    public static function getEntityFqcn(): string
    {
        return RedirectRoute::class;
    }
}
