# Sylius Happy CMS Plugin

Enhance your Sylius with CMS features with this plugin. 

Keep in mind : Happy People make Happy Internet.

## Installation

Actually we don't have Symfony flex configured, so you have to do some installation step manually :

1. Add into `config/packages/sylius_resource.yaml` :

```
imports:
    - { resource: "@SyliusHappyCMSPlugin/config/packages/sylius_resource.yaml" }
```


2. Add into `config/packages/doctrine.yaml` :

```
imports:
    - { resource: "@SyliusHappyCMSPlugin/config/packages/doctrine.yaml" }
```

Then, into `config/bundles.php` add :

```php
Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
```

Then, into `config/packages/_sylius.yaml` add :

```yaml
imports:
  - { resource: "@SyliusHappyCMSPlugin/config/config.yaml" }
```

Then, into `config/routes.yaml` add :

```yaml
sylius_easy_crud:
  resource: "@SyliusHappyCMSPlugin/config/routes.yaml"
```

3. Generate default file in your project (entities, repositories and admin classes) :

Actualy we don't have Symfony recipes, so we created a command to generate files automatically.

```bash
php bin/console make:happy-cms:install
```

This command also provide all variables you need to override. Don't forget to do that!

4. Override default parameters

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
```

5. Déclare sylius_resources

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
```


4. Update database :

```bash
php bin/console doc:mig:diff
php bin/console doc:mig:mig
```

5. Add CMS menu in Sylius

into `config/services.yaml` add :

```yaml
services:
  adeliom.sylius.cms.plugin.listener.admin.menu_builder:
    class: Adeliom\SyliusHappyCMSPlugin\Menu\AdminMenuListener
    tags:
      - { name: kernel.event_listener, event: sylius.menu.admin.main, method: addAdminMenuItems }

```

## Documentation

- TODO
- [Discover all fields](./docs/discover_fields.md) you can use to build your CRUD (grid, form, show, action, fitters)
- Learn how create your [own fields](./docs/create_your_own_fields.md)
- You want to [help and contribute](./docs/contribution.md)

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Authors

Adeliom
