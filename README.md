# Sylius Happy CMS Plugin

Enhance your Sylius with CMS features with this plugin. 

Happy People make a Happy Internet.

## Installation

1. Package installation

```bash
composer require agence-adeliom/sylius-happy-cms-plugin
```

Actually we don't have Symfony flex configured, so you have to do some installation step manually :

2. Add into `config/packages/doctrine.yaml` :

```
imports:
    - { resource: "@SyliusHappyCMSPlugin/config/packages/doctrine.yaml" }
    
doctrine:
    orm:  
        entity_managers:
            default:
                mappings:
                    App:
                        type: attribute
```

Then, into `config/bundles.php` add :

```php
Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
```

Then, into `config/packages/_sylius.yaml` add :

```yaml
imports:
  - { resource: "@SyliusEasyCrudPlugin/config/config.yaml" }
  - { resource: "@SyliusHappyCMSPlugin/config/config.yaml" }
```

Then, into `config/routes.yaml` add :

```yaml
sylius_easy_crud:
  resource: "@SyliusEasyCrudPlugin/config/routes.yaml"
  
sylius_happy_cms:
  resource: "@SyliusHappyCMSPlugin/config/routes.yaml"
```

3. Generate default file in your project (entities, repositories and admin classes) :

Actualy we don't have Symfony recipes, so we created a command to generate files automatically.

```bash
php bin/console make:happy-cms:install
```
This command will :
- Create all entities, repositories and admin class
- update config/routes.yaml by adding route properly declared
- update config/packages/sylius_resource.yaml by adding sylius routes properly declared
- update config/packages/sylius_happy_cms.yaml by adding new files properly declared

If something goes wrong, you can do those actions manually :

3. bis: Override default parameters

Into `config/packages/sylius_happy_cms.yaml` add :

```yaml
sylius_happy_cms:
  page:
    page_model: App\Entity\HappyCMS\Page\Page
    page_repository: App\Repository\HappyCMS\Page\PageRepository
    page_admin: App\Admin\HappyCMS\Page\PageAdmin

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

3. bis: Déclare sylius_resources

Into `config/packages/sylius_resources.yaml` add :

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

3. bis: Add CMS menu in Sylius BO

into `config/services.yaml` add :

```yaml
parameters:
  cmf_routing.dynamic.persistence.orm.route_class: 'App\Entity\HappyCMS\Cmf\Route'
  
services:
  adeliom.sylius.cms.plugin.listener.admin.menu_builder:
    class: Adeliom\SyliusHappyCMSPlugin\Menu\AdminMenuListener
    tags:
      - { name: kernel.event_listener, event: sylius.menu.admin.main, method: addAdminMenuItems }

```

```bash
npm run build
yarn run build
```

5. Update database :

```bash
php bin/console doc:mig:diff
php bin/console doc:mig:mig
php bin/console assets:install --symlink
php bin/console cache:clear
```

Installation finished :tada:

## Going further

1. Generate flex or shared blocks
```bash
php bin/console make:happy-cms:block
php bin/console make:happy-cms:block:shared
```

2. Generate route based models

With this command you will be able to generate a new Sylius resource (Symfony Entity) with generated files and configuration.
Entity, Repository, Admin CRUD, Front-end routes and controllers, CMS features

Example : 
- Generate a Blog
- Generate Faq pages
- Generate Brand page
- Page based on custom Symfony entities

In a second.

TODO :
```bash
php bin/console make:happy-cms:generate-cms-model
```

## Documentation

- Override sylius home page to get the root cms page

```yaml
sylius_shop_homepage:
  path: /{_locale}/
  methods: [GET]
  controller: App\Controller\HomepageController::indexAction
```

in App\Controller\HomepageController :

```php
public function indexAction(Request $request): Response
{
    $page = $this->manager
        ->getRepository(Page::class)
        ->getHomePage($request->getLocale());
    if (!is_null($page) && !is_null($page->getOnlineRoute())) {
        return $this->routeRenderService->renderAction($page, $request, $page->getOnlineRoute());
    } else {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
}
```

- You want to [help and contribute](./docs/contribution.md)

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Authors

Adeliom
