<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;

abstract class AbstractRedirectRouteAdmin extends AbstractAdmin implements RedirectRouteAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_redirect_route_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'id';
    }

    public function configureFilters(): iterable
    {
        yield from parent::configureFilters();
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('Page', 'sylius_happy_cms.page.admin.tab.page');
    }
}
