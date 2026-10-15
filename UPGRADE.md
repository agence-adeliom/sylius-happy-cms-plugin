# Upgrade

## Unreleased (2.3.x)

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
