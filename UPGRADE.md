# Upgrade

## Unreleased (2.3.x)

### Media manager on an existing media library

Nothing to do for a project on the default configuration: every new option defaults to the previous behaviour.
See [docs/medias.md](docs/medias.md#existing-media-library-cdn-base-url-easymedia).

- **`media.directory_check`** (`public_dir` by default, `storage`, `database`): how the manager tells whether a
  folder exists. `public_dir` tests `public/{base_url}/{path}` on the local disk, which is always false with an
  absolute `base_url` (CDN, front domain) or a storage outside the public directory: folders were created again on
  each `createMedia($source, 'a/b')` and folder moves were lost. Set `storage` (or `database`) in that case.
  Folders are now moved file by file on the storage (Flysystem moves files only), with the same result.
- **`media.allowed_mime_types` / `media.allowed_extensions`**: the upload whitelists of `FileValidator`, now
  configurable (defaults: `FileValidator::DEFAULT_ALLOWED_MIME_TYPES` / `DEFAULT_ALLOWED_EXTENSIONS`, the former
  private constants). A configured list replaces the default one. Dangerous extensions, magic bytes and double
  extensions are still checked. `FileValidator` takes the lists as optional constructor arguments.
- **`media.mime_type_source`** (`content` by default, `client`) and **`media.keep_original_extension`** (`false`):
  the MIME type stored on an uploaded media, and so the extension of its slug, comes from the file content
  (`prices.csv` detected as `text/plain` becomes `prices.txt`). `client` keeps the browser MIME type, as EasyMedia
  did; `keep_original_extension` keeps the extension of the uploaded file name. The allowed types are always
  checked on the content.

### Behaviour fixes

- The Twig global `sylius_happy_cms.media` now holds the application configuration (it always held the plugin
  defaults: `base_url: '/'`, `storage_name: 'default.storage'`...). Templates reading it get the configured values.
- `MediaHelper::getMediaRepository()` returns the Doctrine `EntityRepository` of the media entity, whatever its
  class (it returned `null` unless the repository implemented `MediaRepositoryInterface`, and every lookup failed
  silently). Its return type changes from `?MediaRepositoryInterface` to `?EntityRepository`: a subclass overriding
  it must follow.
- `MediaManager::createMedia()` gains two optional arguments, `?FolderInterface $folder = null` (named `folder` as
  in EasyMedia, wins over `$path`) and `array $metas = []` (merged into the detected metas before the save). The
  `$name` of a data URI source is no longer ignored. The media manager upload of a data URI with a name gets that
  name (and slug) instead of a random one.
- `MediaHelper::getMediaByPath()` (public download route) no longer loads every media.
- `GET media.global_search` works again (`?search=`).
- `media_meta(media, key)` returns the meta as stored (array metas such as `dimensions` were `null`); its
  `$default` accepts any type and its return type is `mixed`. `happy_cms_media()` renders oEmbed media again (the
  `code` meta array is passed to the template as `html`, `width`, `height`, `ratio`).
