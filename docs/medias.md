# A VueJS media-manager

## Features

- Image editor
- Multi
    + Upload
    + Move
    + Delete
- Upload by either
    + Using the upload panel
    + Drag&Drop anywhere
    + Click&Hold on an empty area **"items container"**
    + From a url **"images only"**
    + From a url rich embed element like Youtube video
- Preview files before uploading
- Toggle between `random/original` names for uploaded files
- Bulk selection
- Bookmark visited directories for quicker navigation
- Change item/s visibility
- Update the page url on navigation
- Show audio files info **"artist, album, year, etc.."**
- Dynamically hide files / folders
- Restrict access to path
- Download selected "including bulk selection"
- Directly copy selected file link
- Use the manager
    + from modal
    + with any wysiwyg editor
- Auto scroll to selected item using **"left, up, right, down, home, end"**
- Lock/Unlock item/s.
- Filter by
    + Folder
    + Image
    + Audio
    + Oembed
    + Video
    + text/pdf
    + application/archive
    + Locked items
    + Selected items
- Sort by
    + Name
    + Size
    + Last modified
- Items count for
    + All
    + Selected
    + Search found
- File name sanitization for
    + Upload
    + Rename
    + New folder
- Disable/Enable buttons depend on the usage to avoid noise & keep the user focused
- [Shortcuts / Gestures](doc/shortcuts.md)
    + If no more **rows** available, pressing `down` will go to the last item in the list **"same as native file manager"**.
    + When viewing a `audio/video` file in the preview card, pressing `space` will **play/pause** the item instead of closing the modal.
    + Double click/tap
        + any file of type `audio/video/oembed` will open it in the preview card **"same as images"**.
        + any file of type `application/archive` will download it.
    + All the **left/right** gestures have their counterparts available as well.
    + Pressing `esc` while using the ***image editor*** wont close the modal but you can ***dbl click/tap*** the `modal background` to do so. **"to avoid accidentally canceling your changes"**.

> To stop interfering with other `keydown` events you can toggle the manager listener through `EventHub.fire('disable-global-keys', true/false)`.

```php
<?php

declare(strict_types=1);

namespace App\Entity\HappyCMS\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media as BaseMedia;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__media')]
class Media extends BaseMedia
{
}
```

```php
<?php

declare(strict_types=1);

namespace App\Entity\HappyCMS\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder as BaseFolder;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__folder')]
class Folder extends BaseFolder
{
}
```

## Documentation

### Integrate with FOS CKEditor

```yaml
#config/packages/fos_ck_editor.yaml
fos_ck_editor:
    configs:
        main_config:
            ...
            filebrowserBrowseRoute: media.browse
            filebrowserImageBrowseRoute: media.browse
            filebrowserImageBrowseRouteParameters:
                provider: 'image'
                restrict:
                    uploadTypes:
                        - 'image/*'
                    uploadSize: 5
```

### Integrate with LiipImagineBundle

By default, the liip imagine data loader is configured. Look at 'happy.cms.media.imagine.data.loader' service.

Just use the `resolve_media` filter to get the media URL and apply your imagine filter on top of it.
```php
{{ object.media|resolve_media|imagine_filter('filter_name') }}
```

### Field's usage

#### Usage

```php
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\MediaField;
...
yield MediaField::new('property', "label")
    // Apply restrictions by mime-types
    ->setFormTypeOption("restrictions_uploadTypes", ["image/*"])
    // Apply restrictions to upload size in MB
    ->setFormTypeOption("restrictions_uploadSize", 5)
    // Apply restrictions to path
    ->setFormTypeOption("restrictions_path", "users/" . $userID)
    // Hide fiels with extensions (null or array)
    ->setFormTypeOption("hideExt", ["svg"])
    // Hide folders (null or array)
    ->setFormTypeOption("hidePath", ['others', 'users/testing'])
    // Enable/Disable actions
    ->setFormTypeOption("editor", true)
    ->setFormTypeOption("upload", true)
    ->setFormTypeOption("bulk_selection", true)
    ->setFormTypeOption("move", true)
    ->setFormTypeOption("rename", true)
    ->setFormTypeOption("metas", true)
    ->setFormTypeOption("delete", true)
    ;
```

### Twig usage

```php
# Render the media
{{ happy_cms_media(object.media, format, options) }} // By default format is the reference file and options

# Examples :
{{ happy_cms_media(object.media, "reference") }}

{{ happy_cms_media(object.media, "cover_full", {'class': 'myclass'}) }} 

## For images 
{{ happy_cms_media(object.media, "cover_full", {'loading': "lazy"}) }} 
{{ happy_cms_media(object.media, "cover_full", {'picture': ["cover_full__2xl","cover_full__xl","cover_full__lg","cover_full__sm","cover_full__xs"]}) }}
{{ happy_cms_media(object.media, "cover_full", {'srcset': ["cover_full__2xl","cover_full__xl","cover_full__lg","cover_full__sm","cover_full__xs"]}) }}
{{ happy_cms_media(object.media, "cover_full", {'loading': "lazy", 'srcset': {'(max-width: 500px)': 'cover_full__2xl', '(max-width: 1200px)': 'cover_full__xl'}}) }}

## For oembed 
{{ happy_cms_media(object.media, "reference") }}
{{ happy_cms_media(object.media, "reference", {'responsive': true}) }}

## For video 
{{ happy_cms_media(object.media, "reference") }}
{{ happy_cms_media(object.media, "reference", {"responsive" : true, "controls" : true, "autoplay" : true}) }}

# Get media path
{{ happy_cms_media_path(object.media, format) }} // By default format is the reference file

# Get media URL
{{ object.media|resolve_media }}

# Get media metadatas
{{ object.media|media_meta }}

# Get single media metadata, as stored (string, number or array such as "dimensions")
{{ object.media|media_meta('key') }}
{{ (object.media|media_meta('dimensions')).width }}

# Get complete media informations
{{ object.media|media_infos }}

# Get test file type
# type_to_test: can be a mime_type or 
# oembed for any embed type
# image for any image type
# pdf for pdf files
# compressed for archives files
{{ file_is_type(object.media, type_to_test) }}

# Get mimetype icon (font-awesome)
{{ mime_icon("text/plain") }}
```

#### You can override media render with twig

* For images : `@SyliusHappyCMSPlugin/media/render/image.html.twig`
* For oembed : `@SyliusHappyCMSPlugin/media/render/oembed.html.twig`
* For video : `@SyliusHappyCMSPlugin/media/render/oembed.html.twig`

### Manage medias and folders programmatically

```php
/* @var Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager $manager */

# Get media by id or null
$media = $manager->getMedia($id);

# Get folder by id or null
$folder = $manager->getFolder($id);

# Get folder by path
$folder = $manager->folderByPath($path);

# Create a folder
$folder = $manager->createFolder($folderName, $path = null)

# Create a media (see "Create a media from your code" below)
$media = $manager->createMedia($source, $path = null, $name = null, $folder = null, $metas = [])

# Find the media of a storage path ("products/doors/front-door.jpg"), or null
$media = $manager->getHelper()->getMediaByPath($path);

# Save a folder or media
$manager->save($entity, $flush = true);

# Delete a folder or media
$manager->delete($entity, $flush = true);
```

#### Create a media from your code

`MediaManager::createMedia()` writes the file to the media storage and saves the media (it flushes).

| Argument  | Type                       | Description                                                                                                                       |
|-----------|----------------------------|-----------------------------------------------------------------------------------------------------------------------------------|
| `$source` | `string\|File`             | a local file path, a `File` / `UploadedFile`, a data URI (`data:image/png;base64,...`), an image URL or an oEmbed URL (YouTube...) |
| `$path`   | `?string`                  | folder path (`products/doors`); missing folders are created; `null` = root folder. Ignored when `$folder` is given                 |
| `$name`   | `?string`                  | media name, the file slug is built from it (defaults to the file name, or a random name for a data URI)                           |
| `$folder` | `?FolderInterface`         | the folder entity, instead of `$path`                                                                                             |
| `$metas`  | `array<string, mixed>`     | metas added to the detected ones (`dimensions`, oEmbed data...), e.g. `['alt' => '...', 'title' => '...']`                        |

It throws `AlreadyExist` (a media with this name exists in the folder), `ExtNotAllowed` (file type not allowed),
`NoFile` (unreadable source) or `ProviderNotFound` (URL without image nor oEmbed provider), all in
`Adeliom\SyliusHappyCMSPlugin\Exceptions\Media`.

```php
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\AlreadyExist;

// A local file in a folder path, with its alternative text
$media = $manager->createMedia('/tmp/import/front-door.jpg', 'products/doors', metas: ['alt' => 'Front door']);

// In a folder entity, with a name
$media = $manager->createMedia($uploadedFile, name: 'Front door', folder: $folder);

// From a data URI (the name is kept)
$media = $manager->createMedia('data:image/png;base64,iVBORw0...', 'drawings', 'Signature');

try {
    $manager->createMedia($file, 'products/doors');
} catch (AlreadyExist) {
    // same name in the same folder
}
```

The media entity may keep its own repository (a `ServiceEntityRepository` of your application, or the default
Doctrine one): the manager only calls Doctrine methods (`find`, `findBy`, `findOneBy`, `createQueryBuilder`).
`MediaRepositoryInterface` / `MediaRepository` remain available but are not required.

### Use the Doctrine type (optional)

It stores a reference to a media as its id (`BIGINT` column, no foreign key).
It is designed for places where a Doctrine association is not possible, such as `#[ORM\Embeddable]` classes (e.g. `Seo::$cover`).

- On write, it accepts a `MediaInterface`, an int or a numeric string (the value submitted by the media form field) and persists the media id.
- On read, the property contains the raw id, **not** a Media entity. Resolve it when rendering, e.g. with `{{ happy_cms_media(entity.file, 'sylius_small') }}`.

If you need a real Media entity on your own (non-embeddable) entity, prefer a regular `ManyToOne` association.

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            happy_cms_media_type: Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaType
```

In your entity

```php
class Article
{
    #[ORM\Column(type: 'happy_cms_media_type', nullable: true)]
    private MediaInterface|int|null $file;
    
    ...
```

### Configurations

```yaml
# config/packages/sylius_happy_cms.yaml
sylius_happy_cms:
    media:
        
        storage_name: uploads.storage
        base_url: '/media/download'
        media_entity: App\Entity\HappyCMS\Media\Media
        folder_entity: App\Entity\HappyCMS\Media\Folder

        # how the manager tells whether a folder exists:
        # - public_dir (default): public/{base_url}/{path} on the local disk; only valid with a relative base_url
        #   and a storage located in the public directory
        # - storage: asks the Flysystem storage (any adapter, absolute base_url such as a CDN)
        # - database: looks for the folder rows (object storages without directories)
        directory_check: public_dir

        # file types accepted for uploads; each list replaces its default
        # (FileValidator::DEFAULT_ALLOWED_MIME_TYPES / DEFAULT_ALLOWED_EXTENSIONS).
        # Dangerous extensions (php, exe, sh...) stay refused whatever the lists.
        allowed_mime_types: [image/jpeg, image/png, image/avif, ...]   # detected from the file content
        allowed_extensions: [jpg, jpeg, png, avif, ...]

        # MIME type stored on an uploaded media, from which the extension of its slug is derived:
        # content (default, detected from the file) or client (sent by the browser, as EasyMedia did)
        mime_type_source: content

        # keep the extension of the original file name in the slug ("photo.jpg" instead of "photo.jpeg")
        keep_original_extension: false

        # ignore any file starts with "."
        ignore_files:         '/^\..*/'
        
        # remove any file special chars except
        # dot .
        # dash -
        # underscore _
        # single quote ''
        # white space
        # parentheses ()
        # comma ,
        allowed_fileNames_chars: '\._\-\''\s\(\),'
        
        # remove any folder special chars except
        # dash -
        # underscore _
        # white space
        #
        # to add & nest folders in one go add '\/'
        # avoid using '#' as browser interpret it as an anchor
        allowed_folderNames_chars: _\-\s
        
        # disallow uploading files with the following mimetypes (https://www.iana.org/assignments/media-types/media-types.xhtml)
        unallowed_mimes:
            # Defaults:
            - php
            - java
        
        # disallow uploading files with the following extensions (https://en.wikipedia.org/wiki/List_of_filename_extensions)
        unallowed_ext:
            # Defaults:
            - php
            - jav
            - py
    
        extended_mimes:
            # any extra mime-types that doesnt have "image" in it
            image:                # Required
                # Default:
                - binary/octet-stream
            # any extra mime-types that doesnt have "compressed" in it
            archive:              # Required
                # Defaults:
                - application/x-tar
                - application/zip
        
        # display file last modification time as
        last_modified_format: Y-m-d
        
        # hide file extension in files list
        hide_files_ext:       true
        
        # loaded chunk amount "pagination"
        pagination_amount:    50

```

#### Existing media library (CDN base URL, EasyMedia)

When the public URL of the media is absolute (`base_url: 'https://cdn.example.com/media'`) or the storage is not
in the public directory, set `directory_check: storage` (or `database`): with the default check, folders are never
found, `createMedia($source, 'a/b')` creates the folders again and moving a folder does not move its files.

To keep the slugs EasyMedia gave to new files, set `mime_type_source: client` (or `keep_original_extension: true`
to keep the extension of the uploaded file as is), and add the types your library holds to `allowed_mime_types` /
`allowed_extensions`.

The Twig global `sylius_happy_cms.media` (read by the media manager templates) holds this configuration.

#### Global search

`GET media.global_search?search=door` returns the media of every folder whose name contains the term (one query,
files matching `ignore_files` left out), in the format of the folder listing plus `dir_path`. Without `search`,
every media is listed.

### Events

| type            | event-name                                         | description                                                                |
| --------------- | -------------------------------------------------- | -------------------------------------------------------------------------- |
| [JS][js]        |                                                    |                                                                            |
|                 | modal-show                                         | when modal is shown                                                        |
|                 | modal-hide                                         | when modal is hidden                                                       |
|                 | file_selected *(when inside modal)*       | get selected file url                                                      |
|                 | multi_file_selected *(when inside modal)* | get bulk selected files urls                                               |
|                 | folder_selected *(when inside modal)*     | get selected folder path                                                   |
| [Symfony][symfony] |                                                    |                                                                            |
|                 | em.file.uploaded($file_path, $mime_type, $options)   | get uploaded file storage path, mime type |
|                 | em.file.saved($file_path, $mime_type)       | get saved (edited/link) image full storage path, mime type                 |
|                 | em.file.deleted($file_path, $is_folder)              | get deleted file/folder storage path, if removed item is a folder          |
|                 | em.file.renamed($old_path, $new_path)                | get renamed file/folder "old & new" storage path                           |
|                 | em.file.moved($old_path, $new_path)                  | get moved file/folder "old & new" storage path                             |

[js]: https://github.com/gocanto/vuemit
[symfony]: https://symfony.com/doc/current/event_dispatcher.html

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Thanks to

[@arnaud-ritti](https://github.com/arnaud-ritti) (initial adeliom easy-media package contributor)

[ctf0/Laravel-Media-Manager](https://github.com/ctf0/Laravel-Media-Manager)
