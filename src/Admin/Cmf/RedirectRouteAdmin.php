<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;

class RedirectRouteAdmin extends AbstractRedirectRouteAdmin
{
    public static function getName(): string
    {
        return 'sylius_happy_cms_redirect_route_admin';
    }

    public static function getEntityFqcn(): string
    {
        return RedirectRouteInterface::class;
    }
}
