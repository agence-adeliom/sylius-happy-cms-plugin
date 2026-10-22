<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use Adeliom\SyliusHappyCMSPlugin\Command\InvalidCacheCommand;
use Adeliom\SyliusHappyCMSPlugin\Command\MigrateContentToBlocksCommand;
use Adeliom\SyliusHappyCMSPlugin\Command\Starter\CreateDemoMenuCommand;
use Adeliom\SyliusHappyCMSPlugin\Command\Starter\CreateDemoPagesCommand;
use Adeliom\SyliusHappyCMSPlugin\ContextProvider\CreateSharedBlockContextProvider;
use Adeliom\SyliusHappyCMSPlugin\Controller\Routing\RenderController;
use Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Menu\AllMenuItems;
use Adeliom\SyliusHappyCMSPlugin\Doctrine\Query\Page\AllPages;
use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Adeliom\SyliusHappyCMSPlugin\EventListener\ExceptionListener;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\ContentDocumentVoter;
use Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RedirectionManager;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RedirectionManagerInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Adeliom\SyliusHappyCMSPlugin\Twig\Components\Builder\BlockEditor;
use Adeliom\SyliusHappyCMSPlugin\Twig\Components\MenuItemTree\DeleteComponent as MenuItemTreeDeleteComponent;
use Adeliom\SyliusHappyCMSPlugin\Twig\Components\MenuItemTree\TreeComponent as MenuItemTreeTreeComponent;
use Adeliom\SyliusHappyCMSPlugin\Twig\Components\PageTree\DeleteComponent as PageTreeDeleteComponent;
use Adeliom\SyliusHappyCMSPlugin\Twig\Components\PageTree\TreeComponent as PageTreeTreeComponent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->import('services/pages.php');
    $container->import('services/seo.php');
    $container->import('services/menu.php');
    $container->import('services/block.php');
    $container->import('services/maker.php');
    $container->import('services/ai.php');

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(CreateSharedBlockContextProvider::class)
        ->args([service('sylius.happy_cms.shared_block.block_collection')])
        ->tag('sylius.twig_hooks.context_provider');

    $services->set('sylius.happy_cms.plugin.listener.entity_route_indexer', EntityRouteIndexer::class)
        ->args([
            service('cmf_routing.orm_content_repository'),
            service('parameter_bag'),
            service('event_dispatcher'),
            service('doctrine.orm.entity_manager'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'postPersist', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate', 'priority' => 500, 'connection' => 'default']);

    $services->alias(RouteRenderService::class, 'sylius.happy_cms.plugin.services.render')->public();

    $services->alias(MenuRepositoryInterface::class, 'happy_cms_menu.menu.repository');
    $services->alias(MenuItemRepositoryInterface::class, 'happy_cms_menu.menu_item.repository');
    $services->alias(PageRepositoryInterface::class, 'happy_cms_page.page.repository');
    $services->alias(RouteRepositoryInterface::class, 'happy_cms_page.route.repository');

    $services->set('sylius.happy_cms.plugin.services.render', RouteRenderService::class)
        ->public()
        ->args([
            service('router'),
            service('sylius.resource_controller.request_configuration_factory'),
            service('twig'),
            service('event_dispatcher'),
            service('happy_cms_seo.breadcrumb'),
            service('kernel'),
            service('doctrine.orm.entity_manager'),
            service('parameter_bag'),
        ]);

    $services->set('sylius.happy_cms.plugin.controller.render', RenderController::class)
        ->public()
        ->args([service('sylius.happy_cms.plugin.services.render')]);

    $services->set(AssetHappyCMSPackage::class)
        ->tag('assets.package', ['package' => 'sylius.happy.cms.assets.package']);

    $services->set(ContentDocumentVoter::class)
        ->args([service('security.authorization_checker')])
        ->tag('security.voter');

    $services->set('sylius.happy_cms.plugin.security.preview_request_matcher', PreviewRequestMatcher::class)
        ->public();
    $services->alias(PreviewRequestMatcher::class, 'sylius.happy_cms.plugin.security.preview_request_matcher');

    // pages

    // todo interface alias
    $services->set('sylius.happy_cms.page.all_pages', AllPages::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sylius_admin.context.locale.admin_based'),
            service('sylius.translation_locale_provider'),
        ]);

    // todo interface alias
    $services->set('sylius.happy_cms.admin.page_tree.tree', PageTreeTreeComponent::class)
        ->args([service('sylius.happy_cms.page.all_pages')])
        ->tag('sylius.live_component.admin', ['key' => 'happycms-page-tree-tree']);

    // todo interface alias
    $services->set('sylius.happy_cms.admin.page_tree.delete', PageTreeDeleteComponent::class)
        ->args([service('security.csrf.token_manager')])
        ->tag('sylius.live_component.admin', [
            'key' => 'happycms-page-tree-delete',
            'template' => '@SyliusHappyCMSPlugin/page/crud/tree/components/delete.html.twig',
        ]);

    // menu

    // todo interface alias
    $services->set('sylius.happy_cms.menu_item.all_menu_items', AllMenuItems::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('sylius_admin.context.locale.admin_based'),
            service('sylius.translation_locale_provider'),
        ]);

    // todo interface alias
    $services->set('sylius.happy_cms.admin.menu_item_tree.tree', MenuItemTreeTreeComponent::class)
        ->args([service('sylius.happy_cms.menu_item.all_menu_items')])
        ->tag('sylius.live_component.admin', ['key' => 'happycms-menu-item-tree-tree']);

    // todo interface alias
    $services->set('sylius.happy_cms.admin.menu_item_tree.delete', MenuItemTreeDeleteComponent::class)
        ->args([service('security.csrf.token_manager')])
        ->tag('sylius.live_component.admin', [
            'key' => 'happycms-menu-item-tree-delete',
            'template' => '@SyliusHappyCMSPlugin/menu_item/crud/tree/components/delete.html.twig',
        ]);

    $services->set('sylius.happy_cms.plugin.listener.redirection_manager', RedirectionManager::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('parameter_bag'),
        ]);
    $services->alias(RedirectionManagerInterface::class, 'sylius.happy_cms.plugin.listener.redirection_manager');

    $services->set('sylius.happy_cms.exception_listener', ExceptionListener::class)
        ->args([service('sylius.happy_cms.plugin.listener.redirection_manager')])
        ->tag('kernel.event_listener', ['event' => 'kernel.exception']);

    $services->set('sylius.happy_cms.command.invalid_cache', InvalidCacheCommand::class)
        ->args([service('sylius.happy_cms.plugin.services.render')])
        ->tag('console.command');

    $services->set('sylius.happy_cms.command.migrate_content_to_new_block_builder', MigrateContentToBlocksCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('parameter_bag'),
        ])
        ->tag('console.command');

    $services->set('sylius.happy_cms.command.create_demo_pages', CreateDemoPagesCommand::class)
        ->args([
            service('happy.cms.media.manager'),
            service('kernel'),
            service('parameter_bag'),
            service('doctrine.orm.entity_manager'),
            service('logger'),
        ])
        ->tag('console.command');

    $services->set('sylius.happy_cms.command.create_demo_menu', CreateDemoMenuCommand::class)
        ->args([
            service('happy.cms.media.manager'),
            service('kernel'),
            service('parameter_bag'),
            service('doctrine.orm.entity_manager'),
            service('logger'),
        ])
        ->tag('console.command');

    $services->set('sylius.happy_cms.admin.builder.block_editor', BlockEditor::class)
        ->args([
            service('sylius.happy_cms.block_collection'),
            service('doctrine.orm.entity_manager'),
            service('parameter_bag'),
            service('sylius.happy_cms.ai.bundle_detector'),
        ])
        ->call('setLiveResponder', [service('ux.live_component.live_responder')])
        ->tag('sylius.live_component.admin', [
            'key' => 'happycms-builder-block-editor',
            'template' => '@SyliusHappyCMSPlugin/admin/page_builder/_block_editor.html.twig',
        ]);
};
