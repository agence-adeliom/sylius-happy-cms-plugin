# Upgrade

## Unreleased (2.3.x)

### Media and folder slugs: 255 characters

`Media::$slug` and `Folder::$slug` (mapped superclasses) are now `VARCHAR(255)`, like the `name` they are derived
from (they were `VARCHAR(100)`: a file or folder name longer than 100 characters could not be saved on MySQL in
strict mode). The tables belong to your entities, so generate the migration in your project:

```bash
bin/console doctrine:migrations:diff
# ALTER TABLE <media table> CHANGE slug slug VARCHAR(255) NOT NULL
# ALTER TABLE <folder table> CHANGE slug slug VARCHAR(255) NOT NULL
```

Widening a `VARCHAR` keeps the data. A project that already overrides the column (`#[ORM\AttributeOverride]`)
can drop the override for 255, or keep it to stay at another length.

### New Doctrine type `happy_cms_media_reference_type`

Opt-in, nothing changes for existing mappings: `happy_cms_media_type` still reads the raw id.

The new type stores the media id like `happy_cms_media_type` (same `BIGINT` column, no schema change when switching)
but reads it back as a lazy `MediaInterface` reference, without any query until a field other than the id is read.
See [docs/medias.md](docs/medias.md#use-the-doctrine-types-optional) for the behaviour with deleted medias
(reference returned, `EntityNotFoundException` on access, `null` through `MediaManager::getMedia()` and the Twig
helpers) and batch loading.

To use it on a column:

```php
#[ORM\Column(type: MediaReferenceType::TYPE, nullable: true)]
private ?MediaInterface $image = null;
```

and add `'hydrate' => true` to its media form field, so the form writes a `MediaInterface` instead of an id.

Related changes:

- `SyliusHappyCMSPlugin` now implements `boot()` / `shutdown()` (installs the reference factory of the type);
- the media form type (`Adeliom\SyliusHappyCMSPlugin\Form\MediaType`) has a new `hydrate` option (default `false`);
  it now also loads a `MediaInterface` given as model data, so a reference to a deleted media is shown empty instead
  of with its dangling id;
- `MediaHelper::getMedia()` / `MediaManager::getMedia()` initialize a lazy reference they are given (or that `find()`
  returns from the unit of work) and return `null` when the media no longer exists.
