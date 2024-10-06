# Preview

## Access Control

The roles allowed to access a resource preview can be defined using the `ContentPreview` attribute.

The example below only give access to the preview of a page to the EDITOR role.

```php
<?php

namespace App\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Attribute as CMS;use Adeliom\SyliusHappyCMSPlugin\Entity\Page as BasePage;

// .... #[ORM\Entity ... 
#[CMS\ContentPreview(['EDITOR'])]
class Page extends BasePage
{
```

## Route access

To access a preview URL, you have to append `-preview` to the slug of the current resource.
For instance, if you want to access a preview of a page with the slug `my-page`, 
you have to go to the URL `my-page-preview` instead, with the role of EDITOR per the example above.


