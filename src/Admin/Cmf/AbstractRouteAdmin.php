<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateTimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\DateFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;

abstract class AbstractRouteAdmin extends AbstractAdmin implements RouteAdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_route_admin';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'staticPrefix';
    }

    public function configureFilters(): iterable
    {
        yield from parent::configureFilters();

        yield StringFilter::create('staticPrefix', ['staticPrefix'])
            ->setLabel('sylius_happy_cms.route.admin.static_prefix');

        yield BooleanFilter::create('preview')
            ->setLabel('sylius_happy_cms.route.admin.preview')
            ->setDefaultValue('false');

        yield DateFilter::create('lastModification')
            ->setLabel('sylius_happy_cms.route.admin.lastModification');
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = parent::configureActions($pageName);

        $actions->remove(Crud::PAGE_INDEX, Action::NEW);

        $actions->remove(Crud::PAGE_DETAIL, Action::EDIT);
        $actions->remove(Crud::PAGE_INDEX, Action::EDIT);

        $actions->remove(Crud::PAGE_INDEX, Action::DELETE);

        return $actions;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('Route', 'sylius_happy_cms.route.admin.tab.route')
            ->renderHorizontal();

        yield Field::new('host', 'sylius_happy_cms.route.admin.host');

        yield Field::new('staticPrefix', 'sylius_happy_cms.route.admin.static_prefix');

        yield DateTimeField::new('lastModification', 'sylius_happy_cms.route.admin.last_modification');

        yield Field::new('position', 'sylius_happy_cms.route.admin.position')
            ->hideOnIndex();

        yield TabField::new('parameters', 'sylius_happy_cms.route.admin.tab.parameters')
            ->hideOnIndex();

        yield Field::new('methods', 'sylius_happy_cms.route.admin.methods')
            ->hideOnIndex();

        yield Field::new('options', 'sylius_happy_cms.route.admin.options')
            ->hideOnIndex();
    }
}
