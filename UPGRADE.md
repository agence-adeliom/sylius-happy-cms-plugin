# Upgrade

## Unreleased (2.3.x)

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
