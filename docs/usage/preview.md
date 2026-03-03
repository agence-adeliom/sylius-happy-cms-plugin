# Preview

## Preview Modes

The HappyCMS plugin provides two ways to preview content before publishing:

### 1. Query Parameter Preview (New Page Builder)

The new page builder system uses a query parameter `?happy_cms_preview=1` to enable preview mode. This allows you to view draft content directly on the published URL.

**Example:**
```
https://yoursite.com/my-page?happy_cms_preview=1
```

**Features:**
- Shows unpublished blocks and draft content
- Uses custom preview template if configured (see `page_builder.preview_template` configuration)
- Access control managed via security voters
- Works with the new ContentBlock system

### 2. Preview Route (Version < 2.1, without new page builder)

The legacy system uses a separate preview route with `-preview` suffix.

**Example:**
```
https://yoursite.com/my-page-preview
```

## Access Control

The roles allowed to access a resource preview can be defined using the `ContentPreview` attribute.

The example below only gives access to the preview of a page to the EDITOR role.

```php
<?php

namespace App\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Attribute as CMS;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page as BasePage;

// .... #[ORM\Entity ...
#[CMS\ContentPreview(['EDITOR'])]
class Page extends BasePage
{
```

## Using Preview in Twig

You can generate preview URLs using the provided Twig functions:

```twig
{# Generate preview URL with query parameter #}
{{ happy_cms_path(page, true) }}
{# Result: /my-page?happy_cms_preview=1 #}

{# Generate preview URL by ID #}
{{ happy_cms_path_by_id(page.id, 'sylius_happy_cms.page', 'en_US', null, true) }}

{# Generate preview URL by key #}
{{ happy_cms_path_by_key('homepage', 'sylius_happy_cms.page', true) }}
```

## Custom Preview Template

You can configure a custom template for preview mode to show content without header/footer or other layout elements. See the main configuration documentation for details on the `page_builder.preview_template` option.


