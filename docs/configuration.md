1. Into `config/packages/sylius_happy_cms.yaml` add :

```yaml
sylius_happy_cms:
  page:
    page_model: App\Entity\HappyCMS\Page\Page
    page_repository: App\Repository\HappyCMS\Page\PageRepository
    page_admin: App\Admin\HappyCMS\Page\PageAdmin

  page_builder:

    # Optional: custom template for page builder preview (example a simple template without your theme's header/footer)
    preview_template: null  
    
    # Optional: custom system prompt for AI content generation (learn more in docs/AI_CONTENT_GENERATION.md)
    ai_generate_content_prompt: null  

  seo:
    title:
      suffix: ACME

  config:
    config_model: App\Entity\HappyCMS\Config\Config
    config_repository: App\Repository\HappyCMS\Config\ConfigRepository
    config_admin: App\Admin\HappyCMS\Config\ConfigAdmin

  menu:
    menu:
      menu_model: App\Entity\HappyCMS\Menu\Menu
      menu_repository: App\Repository\HappyCMS\Menu\MenuRepository
      menu_admin:  App\Admin\HappyCMS\Menu\MenuAdmin
    menu_item:
      menu_item_model: App\Entity\HappyCMS\Menu\MenuItem
      menu_item_repository: App\Repository\HappyCMS\Menu\MenuItemRepository
      menu_item_admin:  App\Admin\HappyCMS\Menu\MenuItemAdmin

  media:
    storage_name: uploads.storage
    base_url: '/media/download'
    media_entity: App\Entity\HappyCMS\Media\Media
    folder_entity: App\Entity\HappyCMS\Media\Folder

  shared_block:
    shared_block_model: App\Entity\HappyCMS\SharedBlock\SharedBlock
    shared_block_repository: App\Repository\HappyCMS\SharedBlock\SharedBlockRepository
    shared_block_admin: App\Admin\HappyCMS\SharedBlock\SharedBlockAdmin
    
  route:
    route_model: App\Entity\HappyCMS\Cmf\Route
    route_repository: App\Repository\HappyCMS\Cmf\RouteRepository
    route_admin:  App\Admin\HappyCMS\Cmf\RouteAdmin  

  redirect_route:
    redirect_route_model: App\Entity\HappyCMS\Cmf\RedirectRoute
    redirect_route_repository: App\Repository\HappyCMS\Cmf\RedirectRouteRepository
    redirect_route_admin:  App\Admin\HappyCMS\Cmf\RedirectRouteAdmin
```

2. Into `config/packages/sylius_resources.yaml` add :

```yaml
sylius_resource:
  resources:
    sylius_happy_cms.page:
        driver: doctrine/orm
        classes:
          model: App\Entity\HappyCMS\Page\Page
          controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
          repository: App\Repository\HappyCMS\Page\PageRepository
          form: App\Admin\HappyCMS\Page\PageAdmin
        translation:
          classes:
            model: App\Entity\HappyCMS\Page\PageTranslation
            controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
            form: App\Admin\HappyCMS\Page\PageAdmin
    sylius_happy_cms.config:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Config\Config
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        repository: App\Repository\HappyCMS\Config\ConfigRepository
        form: App\Admin\HappyCMS\Config\ConfigAdmin
      translation:
        classes:
          model: App\Entity\HappyCMS\Config\ConfigTranslation
          controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
          form: App\Admin\HappyCMS\Config\ConfigAdmin
    sylius_happy_cms.menu:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Menu\Menu
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        repository: App\Repository\HappyCMS\Menu\MenuRepository
        form: App\Admin\HappyCMS\Menu\MenuAdmin
    sylius_happy_cms.menu_item:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Menu\MenuItem
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        repository: App\Repository\HappyCMS\Menu\MenuItemRepository
        form: App\Admin\HappyCMS\Menu\MenuItemAdmin
      translation:
        classes:
          model: App\Entity\HappyCMS\Menu\MenuItemTranslation
          controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
          form: App\Admin\HappyCMS\Menu\MenuItemAdmin
    sylius_happy_cms.shared_block:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\SharedBlock\SharedBlock
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        repository: App\Repository\HappyCMS\SharedBlock\SharedBlockRepository
        form: App\Admin\HappyCMS\SharedBlock\SharedBlockAdmin
      translation:
        classes:
          model: App\Entity\HappyCMS\SharedBlock\SharedBlockTranslation
          controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
          form: App\Admin\HappyCMS\SharedBlock\SharedBlockAdmin
    sylius_happy_cms.media:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Media\Media
        repository: App\Repository\HappyCMS\Media\MediaRepository
    sylius_happy_cms.media_folder:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Media\Folder
        repository: App\Repository\HappyCMS\Media\FolderRepository
    sylius_happy_cms.route:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Cmf\Route
        repository: App\Repository\HappyCMS\Cmf\RouteRepository
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        form: App\Admin\HappyCMS\Cmf\RouteAdmin
    sylius_happy_cms.redirect_route:
      driver: doctrine/orm
      classes:
        model: App\Entity\HappyCMS\Cmf\RedirectRoute
        repository: App\Repository\HappyCMS\Cmf\RedirectRouteRepository
        controller: Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController
        form: App\Admin\HappyCMS\Cmf\RedirectRouteAdmin
```

3. Into `config/services.yaml` add :

```yaml
parameters:
  cmf_routing.dynamic.persistence.orm.route_class: 'App\Entity\HappyCMS\Cmf\Route'
  
services:
  adeliom.sylius.cms.plugin.listener.admin.menu_builder:
    class: Adeliom\SyliusHappyCMSPlugin\Menu\AdminMenuListener
    tags:
      - { name: kernel.event_listener, event: sylius.menu.admin.main, method: addAdminMenuItems }

```
