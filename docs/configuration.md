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

    # Optional (default true): map the PageContentBlock model of the relational page builder.
    # Set to false if your page has no "contentBlocks" association (see "Doctrine mapping" below).
    map_page_content_block: true

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

## Doctrine mapping

The plugin models (`Page`, `PageTranslation`, `Menu`, `Media`…) are Doctrine mapped superclasses that your
entities extend; their associations target interfaces (`PageInterface`, `RouteInterface`…) resolved with
`doctrine.orm.resolve_target_entities`. Doctrine reads them as parent classes of your entities, as long as their
namespace is mapped (DoctrineBundle auto mapping of the plugin, or an explicit mapping of its `src/Entity`
directories).

### Pages without the relational page builder

`PageContentBlock` owns the `contentOwner` association whose inverse side is `contentBlocks` on your page entity
(`OneToMany`, `mappedBy: 'contentOwner'`, see `make:happy-cms:install`). If your page does not declare
`contentBlocks` (you do not use the relational page builder), `doctrine:schema:validate` reports:

```
The association Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageContentBlock#contentOwner refers to the inverse side
field App\Entity\Page#contentBlocks which does not exist.
```

Leave the model out of the Doctrine mapping:

```yaml
sylius_happy_cms:
  page_builder:
    map_page_content_block: false
```

The other page models stay mapped. Do not use the page builder (`sylius_happy_cms_admin_page_builder` routes,
`MigrateContentToBlocksCommand`) with this setting.

### Inheritance below your entities

Your entity may be the root of a single table or joined inheritance (`App\Entity\Pages\LandingPage extends
App\Entity\Page extends Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page`). Nothing to configure: the plugin
models are Sylius resources, whose to-many associations Sylius `ORMMappedSuperClassSubscriber` removes from the
mapped superclass, and the plugin listener
`Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine\MappedSuperclassAssociationDeclaringClassListener` gives them
back their declaring class, so that the subclasses do not declare `routes` / `children` a second time
(`Property "routes" in "App\Entity\Pages\LandingPage" was already declared, but it must be declared only once`).
