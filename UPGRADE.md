# Upgrade

## Unreleased (2.3.x)

### SEO Twig functions typed on `SeoInterface`

`seo_metas()` and `seo_title()` now accept any `Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface` (they used to
require the `Seo` class, a `TypeError` for any other implementation); `seo_title()` also accepts `null`.

If you extend `Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension` and override `renderSeoMetas()` or
`renderSeoTitle()`, widen the parameter types of your methods:

```diff
-public function renderSeoMetas(?Seo $seo): ?Markup
+public function renderSeoMetas(?SeoInterface $seo): ?Markup

-public function renderSeoTitle(string|Seo $seo): string
+public function renderSeoTitle(string|SeoInterface|null $seo): string
```

### New option: SEO templates

```yaml
sylius_happy_cms:
    seo:
        templates:
            metas: '@SyliusHappyCMSPlugin/front/seo/block-metas.html.twig'
            breadcrumb: '@SyliusHappyCMSPlugin/front/seo/block-breadcrumb.html.twig'
```

The defaults are the current templates. `SeoExtension` takes them as a new, optional, last constructor argument.

### Breadcrumb markup

`front/seo/block-breadcrumb.html.twig` now uses `https://schema.org/BreadcrumbList` and `https://schema.org/ListItem`,
keeps an absolute first link as is (it used to become `https:/host/`) and renders nothing when the breadcrumb is
empty. If you override this template, apply the same changes.

### New options: control the 404 redirections and the SEO Twig extension

Both default to `true` (current behaviour):

```yaml
sylius_happy_cms:
    seo:
        # seo_metas(), seo_title(), seo_breadcrumb() and the happy_cms_seo global
        enable_twig_extension: true
    redirect_route:
        # on a 404, redirect to the matching redirect route when it has a target
        enable_redirection: true
        # on a 404, record the unknown URL as a new redirect route (one row per URL and host)
        enable_not_found_recording: true
```

The two features of the 404 listener can be turned off independently:

| `enable_redirection` | `enable_not_found_recording` | Result |
|---|---|---|
| `true` | `true` | Current behaviour: redirections are served, unknown URLs are recorded. |
| `true` | `false` | Redirections configured in the admin are served, the `redirect_route` table no longer grows with crawler and typo URLs. |
| `false` | `true` | Unknown URLs are recorded, but nothing is redirected (collect the URLs first, enable the redirections later). |
| `false` | `false` | The listener `sylius.happy_cms.exception_listener` is not registered. |

If you removed `sylius.happy_cms.exception_listener` (both options to `false`) or the `twig.extension` tag of
`Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension` in a compiler pass, you can use these options instead.
With `enable_twig_extension: false`, the `SeoExtension` service stays defined (for its constants and for
your own services) but its functions are no longer available in Twig: override the plugin templates
that call them (`default_layout.html.twig`, `front/document/default.html.twig`, `front/seo/*`,
`front/template_block/seo_metas.html.twig`) or provide functions with the same names.

### Admin routes no longer hard-code `/admin`

The plugin admin routes no longer contain `/admin` in their path:

- `sylius_happy_cms_admin_shared_block_select`, `sylius_happy_cms_admin_menu_item_index`,
  `sylius_happy_cms_admin_page_clear_cache`, `sylius_happy_cms_admin_page_builder`,
  `sylius_happy_cms_admin_page_builder_block_edit` moved to `config/routes/admin.yaml`;
- the 18 media manager routes (`media.*`) are in `config/routes/media_admin.php`.

`config/routes.yaml` is kept as the backward compatible entry point: it imports these files with
`prefix: '/%sylius_admin.path_name%'`.

- **You import `config/routes.yaml` and use the default admin path (`/admin`)**:
  nothing changes, same route names and same paths.
- **You changed `SYLIUS_ADMIN_ROUTING_PATH_NAME`**: the routes now follow it (e.g. `/back-office/pages/clear-cache`)
  and are therefore covered by the admin firewall. Templates generate them with `path()`, so no change is
  expected in your code unless you hard-coded one of these URLs.
- **Your admin is served from another prefix or host**: import `@SyliusHappyCMSPlugin/config/routes/admin.yaml`,
  `@SyliusHappyCMSPlugin/config/routes/media_admin.php` and `@SyliusHappyCMSPlugin/config/routes/media_public.php`
  directly, instead of `config/routes.yaml`, with your own `prefix` / `host`.

`make:happy-cms:install` now generates `prefix: '/%sylius_admin.path_name%'` instead of `prefix: admin` for the
CRUD routes it writes in `config/routes.yaml`. Routes generated before keep working as they are.
`make:happy-cms:generate-cms-model` prints the route configuration built by the easy-crud plugin
(`CrudMakerService::generateRoute()`), which follows the same rule from the easy-crud version that ships it.

### `MediaLoader` removed, media routes imported by `config/routes.yaml`

The `Adeliom\SyliusHappyCMSPlugin\Routing\MediaLoader` route loader has been removed. No action is required:
the media routes are now defined in `config/routes/media_admin.php` (media manager, `media.*`) and
`config/routes/media_public.php` (`media.file_download`, `/media/download/{path}`), and imported by the plugin
`config/routes.yaml` that your project already imports.

`MediaLoader` was registered for every route import of `type: attribute`, with a higher priority than the
Symfony attribute loaders: an import such as

```yaml
controllers:
    resource: ../src/Controller/
    type: attribute
```

returned the 18 media manager routes instead of the project's own controller routes. Those imports now load the
project routes.

- **You had removed the `routing.loader` tag of `MediaLoader`** (compiler pass) to get your attribute routes back:
  the workaround can be deleted.
- **The public route `media.file_download` is new**: `config/media/public_routes.php` was never loaded before. It is
  now registered, as `sylius_happy_cms.media.base_url` is `/media/download` by default. If you already define a
  route on this path, it takes precedence or conflicts depending on the import order.
- The route files moved from `config/media/routes.php` and `config/media/public_routes.php` to
  `config/routes/media_admin.php` and `config/routes/media_public.php`. Only matters if you imported them directly.

### New option: path of the media manager

`sylius_happy_cms.media.admin_path` sets the path of the media manager routes (`media.*`) below the prefix of their
import. The default, `happy-cms-media/medias`, keeps the current URLs: nothing to do.

```yaml
sylius_happy_cms:
    media:
        admin_path: medias   # media manager on /admin/medias/…
```

Leading and trailing `/` are removed (`/medias` and `medias` are the same). The routes are generated with `path()`, so templates and the media
manager JavaScript follow it.

### Inheritance below an entity extending a plugin model

`Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine\MappedSuperclassAssociationDeclaringClassListener` (Doctrine
`loadClassMetadata` listener, priority -100) is registered by the plugin. It sets the declaring class of the
associations that Sylius `ORMMappedSuperClassSubscriber` removes from the plugin mapped superclasses (`routes`,
`children`, `items`…), which lets a single table or joined inheritance subclass of your entity load (it failed with
`Property "routes" … was already declared`). Nothing to configure, no schema change: only the entities mapped with an
inheritance are changed, and only their associations without a declaring class whose property belongs to a plugin
class. An entity without inheritance (e.g. `App\Entity\Page extends Adeliom\SyliusHappyCMSPlugin\Entity\Page\Page`
and nothing below it) keeps exactly the same metadata. If your project has its own listener for this, you can remove
it.

## 2.3.1

### `agence-adeliom/sylius-easy-crud-plugin` ^2.3.2 is required

easy-crud 2.3.2 removes `CrudAdminFactory`, which the happy-cms Admins used. happy-cms now requires
`agence-adeliom/sylius-easy-crud-plugin: ^2.3.2`, and easy-crud 2.3.2 declares a Composer `conflict` with happy-cms < 2.3.1:
the two plugins must be updated together.

```bash
composer update agence-adeliom/sylius-easy-crud-plugin agence-adeliom/sylius-happy-cms-plugin
```

- **Your Admins only implement `configure*()` methods** (Admins generated by `make:happy-cms:install` or
  `make:happy-cms:generate-cms-model`): nothing to do, the new constructor arguments are autowired.
- **An Admin uses `$this->crudAdminFactory`**: use the property of the Admin instead.

  | Before | After |
  |---|---|
  | `$this->crudAdminFactory->getPropertyAccessor()` | `$this->propertyAccessor` |
  | `$this->crudAdminFactory->requestStack` | `$this->requestStack` |
  | `$this->crudAdminFactory->parameterBag` | `$this->parameterBag` |

- **An Admin overrides the constructor**: declare the new arguments of easy-crud's `AbstractFormType` and pass them
  to `parent::__construct()`.
- **A controller extending `SyliusCrudResourceController` calls `resetBuild()`**: remove the call, the method no
  longer exists.

See the [easy-crud 2.3.2 upgrade notes](https://github.com/agence-adeliom/sylius-easy-crud-plugin/blob/2.3.x/UPGRADE.md)
for the full list of changes (removed services, `CrudViewBuilder`, resource context).

`ConfigTranslatableDataMapper` and `ContentTranslatableDataMapper` now type their property accessor argument with
`PropertyAccessorInterface` instead of the concrete `PropertyAccessor`. Code that instantiates them keeps working.
