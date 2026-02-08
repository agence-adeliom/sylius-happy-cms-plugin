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

# Get single media metadata
{{ object.media|media_meta('key') }}

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

# Create a media
# $source can be a UploadedFile, File, Image URL, Oembed URL, Base64 URI
$folder = $manager->createMedia($source, $path = null, $name = null)

# Save a folder or media
$manager->save($entity, $flush = true);

# Delete a folder or media
$manager->delete($entity, $flush = true);
```

### Use the Doctrine type (optional)

It automatically converts the stored path into a Media entity

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
    private Media|string|null $file;
    
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
