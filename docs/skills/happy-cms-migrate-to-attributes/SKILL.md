---
name: happy-cms-migrate-to-attributes
description: Migrate HappyCMS admin resources (page, shared_block, menu, config, route, redirect_route, menu_item) from legacy YAML declaration (config/routes.yaml `type: sylius.resource` + config/packages/sylius_resource.yaml) to the `#[AsAdmin]` attribute on Admin classes. Use when upgrading easy-crud to 2.1+ and see deprecations about "Declaring the easy-crud resource ... through legacy YAML" for HappyCMS resources.
---

# Migrate HappyCMS resources to `#[AsAdmin]` attributes

Since easy-crud 2.1, HappyCMS admin resources are declared with **one attribute** instead of two legacy YAML blocks:

- **`#[Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin]` on the Admin class** — the easy-crud layer with admin UI/routing metadata.

Unlike generic easy-crud resources, HappyCMS does **not** require `#[\Sylius\Resource\Metadata\AsResource]` on the entity because the entity class and alias are already defined via the `getName()` and `getEntityFqcn()` methods on the Admin class (which you will preserve).

This skill performs that migration safely, one resource at a time.

## Golden rules

- **Preserve `alias` + `section`.** Route names and URLs are derived only from these. Keep them identical or you break links, redirects and templates. The alias is embedded in the attribute.
- **Preserve the grid name.** It is `Admin::getName()`; the grid is registered under it. **Do not** capture it into the attribute unless it differs from the auto-derived name (see Step 3). Keep the method.
- **Keep `getName()` and `getEntityFqcn()`.** They are still called by easy-crud; do not remove them.
- **One resource at a time, then verify.** The legacy and attribute systems coexist, so migrate, run the verification, then move on.
- **Never invent values.** Only move what already exists in the YAML / Admin into the attributes.
- **Media and media_folder stay in YAML.** These have no Admin class, so skip them entirely.

## Prerequisites

1. `agence-adeliom/sylius-easy-crud-plugin` is `>= 2.1`.
2. Enable discovery **once for the project** — single path list in the app:

   ```yaml
   # config/packages/sylius_easy_crud.yaml
   sylius_easy_crud:
       attributes:
           enabled: true
           paths: ['%kernel.project_dir%/src/Admin/HappyCMS']
   ```

   This tells easy-crud where to scan for `#[AsAdmin]` classes. If you scaffolded Admin classes in a different namespace (e.g. `App\Admin\HappyCMS\*`), list that path instead.

3. Do **not** add `sylius_resource.mapping.paths` — it is already handled by the plugin bundle at `Adeliom\SyliusHappyCMSPlugin\Entity`.

## Migration table

Each HappyCMS resource has a fixed Admin class and configuration. Use this table to apply the correct `#[AsAdmin]` arguments:

| Resource | Alias | Admin Class (in plugin) | Entity | Controller | Translatable | Special notes |
|----------|-------|------------------------|--------|-----------|--------------|--------------|
| **page** | `sylius_happy_cms.page` | `Adeliom\SyliusHappyCMSPlugin\Admin\Page\PageAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface` | `Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageResourceController` | Yes | Custom templates + page builder context |
| **shared_block** | `sylius_happy_cms.shared_block` | `Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\SharedBlockAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock` | Default (SyliusCrudResourceController) | Yes | Standard crud |
| **config** | `sylius_happy_cms.config` | `Adeliom\SyliusHappyCMSPlugin\Admin\Config\ConfigAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Config\Config` | Default | Yes | Standard crud |
| **menu** | `sylius_happy_cms.menu` | `Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Menu\Menu` | Default | No | Standard crud |
| **menu_item** | `sylius_happy_cms.menu_item` | `Adeliom\SyliusHappyCMSPlugin\Admin\Menu\MenuItemAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItem` | Default | Yes | `except: ['index']` + custom templates |
| **route** | `sylius_happy_cms.route` | `Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RouteAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RouteInterface` | Default | No | Standard crud |
| **redirect_route** | `sylius_happy_cms.redirect_route` | `Adeliom\SyliusHappyCMSPlugin\Admin\Cmf\RedirectRouteAdmin` | `Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute` | Default | No | Standard crud |
| media | — | — | — | — | — | **Stay in YAML** (no admin) |
| media_folder | — | — | — | — | — | **Stay in YAML** (no admin) |

## Step 1 — Enable discovery

Edit `config/packages/sylius_easy_crud.yaml`:

```yaml
sylius_easy_crud:
    attributes:
        enabled: true
        paths: ['%kernel.project_dir%/src/Admin/HappyCMS']
```

This is a one-time setup for the entire project.

## Step 2 — Pick a HappyCMS resource

Choose one from the table above (not `media` or `media_folder`) and locate:
- Its Admin class in the plugin: `Adeliom\SyliusHappyCMSPlugin\Admin\*\*Admin.php`
- Its two YAML declarations in `tests/TestApplication/config/`:
  - `config/packages/sylius_resource.yaml` — the `sylius_resource.resources.<alias>` block.
  - `config/routes.yaml` — the `sylius_happy_cms_*_admin` route block (with `type: sylius.resource`).

Example for `shared_block`:
```bash
grep -n "sylius_happy_cms.shared_block" tests/TestApplication/config/packages/sylius_resource.yaml tests/TestApplication/config/routes.yaml
```

## Step 3 — Build the `#[AsAdmin]` attribute

Map the route block (`config/routes.yaml`) to the attribute using this table. **Omit any argument that equals the default.**

| YAML field | Argument | Default | Notes |
|-----------|----------|---------|-------|
| `alias` | `alias:` | `'sylius_happy_cms.<resource>'` | Always set (from the table). |
| `section` | `section:` | `'admin'` | Omit unless you override. |
| `prefix` (import level) | `prefix:` | `'admin'` | Omit unless you override. |
| `redirect` | `redirect:` | `'update'` | Omit unless you override. |
| `grid` (= `Admin::getName()`) | `grid:` | auto-derived from alias | **Usually omitted** — easy-crud will derive it as `sylius_happy_cms_<resource>_admin`. Check if the YAML `grid:` equals this; if yes, omit it. |
| `form.type` (the Admin) | (implicit, don't set) | — | The Admin class is discovered by path scan; no argument needed. |
| `templates` | `templates:` | `'@SyliusEasyCrudPlugin\crud'` | Set if you use a custom template dir (e.g., `page` uses `@SyliusHappyCMSPlugin\page\crud`). |
| `except` / `only` | `except:` / `only:` | `[]` | Set only for `menu_item` (`except: ['index']`). |
| `vars.all.icon` | `icon:` | `'file'` | Omit (icon is `file` for all). |
| `vars.all.subheader` | `subheader:` | auto-derived i18n key | Almost always omit — easy-crud derives it as `'sylius_happy_cms.<resource>.admin.ui.subheader'`. |
| `vars.all.breadcrumb` | `breadcrumb:` | auto-derived i18n key | Almost always omit — easy-crud derives it as `'sylius_happy_cms.<resource>.admin.ui.index'`. |
| `vars.*.header` (per action) | `header:` or per-action in `vars:` | auto-derived i18n keys | Omit — easy-crud auto-derives them. |
| `classes.controller` (if not default) | `controller:` | `SyliusCrudResourceController` | Set only for `page` (`Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageResourceController`). |
| any custom `vars` (action-specific overrides) | `vars:` (free-form) | `[]` | Only if YAML has custom keys beyond standard headers/redirects. |

**Golden rule**: If the YAML already uses auto-derivable i18n keys and templates, **omit them from the attribute** — easy-crud will regenerate them.

### Examples

#### shared_block (simplest)
YAML declares default routing and standard headers. The attribute is minimal:

```php
#[AsAdmin(
    alias: 'sylius_happy_cms.shared_block',
)]
class SharedBlockAdmin extends AbstractSharedBlockAdmin implements ...
{
    public static function getName(): string { return 'sylius_happy_cms_shared_block_admin'; }
    public static function getEntityFqcn(): string { return SharedBlock::class; }
}
```

#### page (custom controller + templates)
YAML specifies a custom controller and custom template directory. The attribute includes both:

```php
#[AsAdmin(
    alias: 'sylius_happy_cms.page',
    controller: 'Adeliom\SyliusHappyCMSPlugin\Controller\Page\PageResourceController::class',
    templates: '@SyliusHappyCMSPlugin\page\crud',
)]
class PageAdmin extends AbstractPageAdmin
{
    public static function getName(): string { return 'sylius_happy_cms_page_admin'; }
    public static function getEntityFqcn(): string { return PageInterface::class; }
}
```

#### menu_item (except + custom vars)
YAML excludes the `index` action and uses custom breadcrumb templates. The attribute includes both:

```php
#[AsAdmin(
    alias: 'sylius_happy_cms.menu_item',
    except: ['index'],
    vars: [
        'update' => [
            'templates' => [
                'breadcrumb' => '@SyliusHappyCMSPlugin\menu_item\crud\_breadcrumb.html.twig',
            ],
        ],
    ],
)]
class MenuItemAdmin extends AbstractMenuItemAdmin implements MenuItemAdminInterface
{
    public static function getName(): string { return 'sylius_happy_cms_menu_item_admin'; }
    public static function getEntityFqcn(): string { return MenuItem::class; }
}
```

## Step 4 — Apply the attribute to the Admin class

The plugin Admin classes (in `Adeliom\SyliusHappyCMSPlugin\Admin\*`) are already written. **Do not edit them directly.** Instead, create an **app-level override** in your application's `src/Admin/HappyCMS/` directory:

### Example: Create `src/Admin/HappyCMS/SharedBlock/SharedBlockAdmin.php`

```php
<?php

declare(strict_types=1);

namespace App\Admin\HappyCMS\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlock;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock\AbstractSharedBlockAdmin;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsAdmin(
    alias: 'sylius_happy_cms.shared_block',
)]
class SharedBlockAdmin extends AbstractSharedBlockAdmin implements ServiceSubscriberInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'sylius_happy_cms_shared_block_admin';
    }

    public static function getEntityFqcn(): string
    {
        return SharedBlock::class;
    }
}
```

**Key points:**
- Place it in `src/Admin/HappyCMS/<ResourceName>/` (mirrors the plugin structure).
- Extend the **abstract base** class from the plugin (e.g., `AbstractSharedBlockAdmin`).
- **Do not remove** `getSubscribedServices()`, `getName()`, or `getEntityFqcn()`.
- Add the `#[AsAdmin(...)]` attribute above the class.
- If the class implements `ServiceSubscriberInterface` **and** `getSubscribedServices()` returns `[]`, you may optionally remove both; otherwise keep them.

### Service configuration (optional)

If your app does not auto-register Admin classes, add them to `config/services.yaml`:

```yaml
App\Admin\HappyCMS\:
    resource: '%kernel.project_dir%/src/Admin/HappyCMS'
    public: false
```

Most modern Symfony apps auto-register by default; check if it's already there.

## Step 5 — Remove the legacy YAML blocks

Once the `#[AsAdmin]` is in place on the app-level Admin class, delete:

1. **The route block** in `config/routes.yaml` — the entire `sylius_happy_cms_<resource>_admin` entry with `type: sylius.resource`.
2. **The resource block** in `config/packages/sylius_resource.yaml` — the entire `sylius_resource.resources.sylius_happy_cms.<resource>` entry (including its `translation:` sub-block).

**Leave media and media_folder YAML untouched.**

Example: to remove `shared_block`, delete these two blocks (from tests/TestApplication/config/):

```yaml
# config/packages/sylius_resource.yaml - DELETE:
sylius_happy_cms.shared_block:
    driver: doctrine/orm
    classes:
        model: Tests\...
        controller: Adeliom\...
        repository: Tests\...
        form: Tests\...
    translation:
        # ...

# config/routes.yaml - DELETE:
sylius_happy_cms_shared_block_admin:
    resource: |
        alias: sylius_happy_cms.shared_block
        # ...
    type: sylius.resource
    prefix: admin
```

## Step 6 — Verify

```bash
# 1. If using DDEV, prefix commands with `ddev`:
# ddev bin/console ...

# 2. Clear cache:
bin/console cache:clear

# 3. Check that routes are unchanged:
bin/console debug:router | grep sylius_happy_cms.<resource> | sort

# 4. Confirm the controller is the easy-crud one (not the default):
bin/console debug:container sylius_happy_cms.<resource>.controller

# 5. Ensure no more deprecations for this resource:
bin/console debug:container --deprecations | grep sylius_happy_cms.<resource>
```

Open the admin screens (index, create, edit, delete) in the browser to confirm the grid, form and redirects still work.

## Step 7 — Repeat

Go back to Step 2 for the next HappyCMS resource until all are migrated. Final check:

```bash
bin/console debug:container --deprecations | grep sylius_happy_cms   # should be empty
```

If the result is empty, all HappyCMS resources are migrated.

---

## Troubleshooting

### Double-declaration error (routes conflict)

**Symptom**: "Cannot redeclare route name `sylius_happy_cms.shared_block.index`."

**Cause**: You added the `#[AsAdmin]` but did not remove the legacy YAML blocks. Both systems are trying to declare the same routes.

**Fix**: Delete the `sylius_happy_cms_<resource>_admin` entry from `config/routes.yaml` and the `sylius_resource.resources.sylius_happy_cms.<resource>` block from `config/packages/sylius_resource.yaml`.

### Admin class not discovered

**Symptom**: Routes still fail after adding the attribute; deprecation still reports "legacy YAML."

**Cause**: The app-level Admin class is not in a scanned path.

**Fix**: 
1. Ensure `config/packages/sylius_easy_crud.yaml` lists your Admin directory:
   ```yaml
   sylius_easy_crud:
       attributes:
           paths: ['%kernel.project_dir%/src/Admin/HappyCMS']
   ```
2. Run `bin/console cache:clear`.
3. If using DDEV, ensure you are running inside the container (`ddev bin/console ...`).

### i18n keys not translating

**Symptom**: Admin headers show `sylius_happy_cms.shared_block.admin.ui.index` as plain text instead of the translation.

**Cause**: The resource's translation files are missing from the app.

**Fix**: Ensure your app has the locale i18n files in `translations/` (they are usually inherited from the plugin). If not, copy them from the plugin's `src/Resources/translations/`.

---

## Summary

To migrate a HappyCMS resource:

1. **Enable discovery** (once): set `sylius_easy_crud.attributes.paths` in `config/packages/sylius_easy_crud.yaml`.
2. **Create app-level Admin** in `src/Admin/HappyCMS/<ResourceName>/` extending the plugin's abstract.
3. **Add `#[AsAdmin]`** with the resource's alias (from the migration table).
4. **Delete the two YAML blocks** (route + resource) for that resource.
5. **Run `bin/console cache:clear` and verify** routes are unchanged; open the admin screen.
6. **Repeat** for the next resource.

When all are done, `bin/console debug:container --deprecations | grep sylius_happy_cms` should return nothing.
