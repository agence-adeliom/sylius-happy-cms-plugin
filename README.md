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
Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
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

Into `config/parameters.yaml` add :


```yaml
parameters:
    sylius_happy_cms.page.model: App\Entity\HappyCMS\Page\Page
    sylius_happy_cms.page.model_translation: App\Entity\HappyCMS\Page\PageTranslation
    sylius_happy_cms.page.repository: App\Repository\HappyCMS\Page\PageRepository
    sylius_happy_cms.page.page_admin: App\Admin\HappyCMS\Page\PageAdmin

    sylius_happy_cms.config.model: App\Entity\HappyCMS\Config\Config
    sylius_happy_cms.config.model_translation: App\Entity\HappyCMS\Config\ConfigTranslation
    sylius_happy_cms.config.repository: App\Repository\HappyCMS\Config\ConfigRepository
    sylius_happy_cms.config.config_admin: App\Admin\HappyCMS\Config\ConfigAdmin

    sylius_happy_cms.folder.model: App\Entity\HappyCMS\Media\Folder
    sylius_happy_cms.folder.repository: App\Repository\HappyCMS\Media\FolderRepository
    sylius_happy_cms.media.model: App\Entity\HappyCMS\Media\Media
    sylius_happy_cms.media.repository: App\Repository\HappyCMS\Media\MediaRepository

    sylius_happy_cms.menu.model: App\Entity\HappyCMS\Menu\Menu
    sylius_happy_cms.menu.repository: App\Repository\HappyCMS\Menu\MenuRepository
    sylius_happy_cms.menu.menu_admin: App\Admin\HappyCMS\Menu\MenuAdmin

    sylius_happy_cms.menu_item.model: App\Entity\HappyCMS\Menu\MenuItem
    sylius_happy_cms.menu_item.model_translation: App\Entity\HappyCMS\Menu\MenuItemTranslation
    sylius_happy_cms.menu_item.repository: App\Repository\HappyCMS\Menu\MenuItemRepository
    sylius_happy_cms.menu_item.menu_admin: App\Admin\HappyCMS\Menu\MenuItemAdmin

    sylius_happy_cms.shared_block.model: App\Entity\HappyCMS\SharedBlock\SharedBlock
    sylius_happy_cms.shared_block.model_translation: App\Entity\HappyCMS\SharedBlock\SharedBlockTranslation
    sylius_happy_cms.shared_block.repository: App\Repository\HappyCMS\SharedBlock\SharedBlockRepository
    sylius_happy_cms.shared_block.menu_admin: App\Admin\HappyCMS\SharedBlock\SharedBlockAdmin
```

5. Déclare sylius_resources

Into `config/packages/sylius_resources.yaml` add :

```yaml
sylius_resource:
  resources:
    sylius_happy_cms.page:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.page.model%"
        controller: "%sylius_happy_cms.page.controller%"
        repository: "%sylius_happy_cms.page.repository%"
        form: "%sylius_happy_cms.page.page_admin%"
      translation:
        classes:
          model: "%sylius_happy_cms.page.model_translation%"
          controller: "%sylius_happy_cms.page.controller_translation%"
          form: "%sylius_happy_cms.page.page_admin%"
    sylius_happy_cms.config:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.config.model%"
        controller: "%sylius_happy_cms.config.controller%"
        repository: "%sylius_happy_cms.config.repository%"
        form: "%sylius_happy_cms.config.config_admin%"
      translation:
        classes:
          model: "%sylius_happy_cms.config.model_translation%"
          controller: "%sylius_happy_cms.config.controller_translation%"
          form: "%sylius_happy_cms.config.config_admin%"
    sylius_happy_cms.menu:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.menu.model%"
        controller: "%sylius_happy_cms.menu.controller%"
        repository: "%sylius_happy_cms.menu.repository%"
        form: "%sylius_happy_cms.menu.menu_admin%"
    sylius_happy_cms.menu_item:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.menu_item.model%"
        controller: "%sylius_happy_cms.menu_item.controller%"
        repository: "%sylius_happy_cms.menu_item.repository%"
        form: "%sylius_happy_cms.menu_item.menu_item_admin%"
      translation:
        classes:
          model: "%sylius_happy_cms.menu_item.model_translation%"
          controller: "%sylius_happy_cms.menu_item.controller_translation%"
          form: "%sylius_happy_cms.menu_item.menu_item_admin%"
    sylius_happy_cms.shared_block:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.shared_block.model%"
        controller: "%sylius_happy_cms.shared_block.controller%"
        repository: "%sylius_happy_cms.shared_block.repository%"
        form: "%sylius_happy_cms.shared_block.shared_block_admin%"
      translation:
        classes:
          model: "%sylius_happy_cms.shared_block.model_translation%"
          controller: "%sylius_happy_cms.shared_block.controller_translation%"
          form: "%sylius_happy_cms.shared_block.shared_block_admin%"
    sylius_happy_cms.media:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.media.model%"
        repository: "%sylius_happy_cms.media.repository%"
    sylius_happy_cms.media_folder:
      driver: doctrine/orm
      classes:
        model: "%sylius_happy_cms.folder.model%"
        repository: "%sylius_happy_cms.folder.repository%"
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
