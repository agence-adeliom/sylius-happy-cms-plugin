<div align="center">

# Content Blocks System

</div>

## Table of Contents

- [Overview](#overview)
- [Block Types](#block-types)
- [Creating Blocks](#creating-blocks)
- [Block Structure](#block-structure)
- [Assets Management](#assets-management)
- [Default Blocks](#default-blocks)
- [Overriding Blocks](#overriding-blocks)
- [Display blocks on front-end](#display-block-on-front-end)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

## Overview

The Sylius Happy CMS Plugin provides a powerful block system that allows you to create reusable content components. Blocks can be added to CMS pages through the page builder interface, making it easy to build rich, dynamic content layouts.

## Block Types

### Flex Blocks

**Flex blocks** are content blocks created and managed directly within a page. They are unique to the page they're created on and cannot be reused across multiple pages.

**Use cases**:
- Page-specific hero sections
- Unique testimonials or quotes
- Custom content that won't be repeated
- One-off promotional sections

**Extends**: `Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock`

### Shared Blocks

**Shared blocks** are reusable blocks that can be created once and used across multiple pages. They are managed separately from pages and can be updated in one place to reflect changes across all pages that use them.

**Use cases**:
- Newsletter signup forms
- Contact information sections
- Social media feeds
- Consistent call-to-action elements
- Global promotional banners

**Extends**: `Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType`

## Creating Blocks

### Using Commands (Recommended)

#### Generate Flex Block

```bash
php bin/console make:happy-cms:block
```

This interactive command will:
1. Ask for the block name
2. Create the block class in `src/Block/`
3. Create the template in `templates/front/blocks/`
4. Automatically register the block

#### Generate Shared Block

```bash
php bin/console make:happy-cms:block:shared
```

Same process as flex blocks but for reusable shared blocks.

### Manual Creation

#### Flex Block Example

Create file `src/Block/TestimonialBlock.php`:

```php
<?php

declare(strict_types=1);

namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Model\Assets\AssetHappyCMSPackage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class TestimonialBlock extends AbstractBlock
{
    /**
     * Build the form that configures this block
     * Fields defined here appear in the page builder admin interface
     */
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'required' => true,
                'label' => 'Author Name',
            ])
            ->add('position', TextType::class, [
                'required' => false,
                'label' => 'Position/Title',
            ])
            ->add('quote', TextareaType::class, [
                'required' => true,
                'label' => 'Testimonial Text',
                'attr' => ['rows' => 5],
            ])
            ->add('rating', IntegerType::class, [
                'required' => false,
                'label' => 'Rating (1-5)',
                'attr' => ['min' => 1, 'max' => 5],
            ]);
    }

    /**
     * Display name in block selection menu
     * Use translation keys for multi-language support
     */
    public function getName(): string
    {
        return 'Testimonial Block';
    }

    /**
     * Group blocks in selection menu by category
     * Common tabs: text_blocks, media_blocks, layout_blocks
     */
    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.text_blocks';
    }

    /**
     * Icon displayed in block selection menu
     * Can use image URL or HTML markup
     */
    public function getIcon(): string
    {
        return '<i class="icon-quote"></i>';
        // Or use image:
        // return '<img src="/path/to/icon.png" alt="Testimonial">';
    }

    /**
     * Path to front-end template
     */
    public function getFrontEndTemplatePath(): string
    {
        return '@App/front/blocks/testimonial_block.html.twig';
    }

    /**
     * Position in block selection menu (lower = higher priority)
     */
    public function getPosition(): int
    {
        return 50;
    }
}
```

#### Shared Block Example

Create file `src/Block/NewsletterBlock.php`:

```php
<?php

declare(strict_types=1);

namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class NewsletterBlock extends AbstractSharedBlockType
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('heading', TextType::class, [
                'required' => true,
                'label' => 'Newsletter Heading',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
            ])
            ->add('buttonText', TextType::class, [
                'required' => true,
                'label' => 'Button Text',
                'data' => 'Subscribe',
            ]);
    }

    public function getName(): string
    {
        return 'Newsletter Signup';
    }

    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.shared_blocks';
    }

    public function getIcon(): string
    {
        return '<i class="icon-envelope"></i>';
    }

    public function getFrontEndTemplatePath(): string
    {
        return '@App/front/blocks/newsletter_block.html.twig';
    }
}
```

## Block Structure

### Required Methods

All blocks must implement these methods from `BlockTypeInterface`:

#### `getName(): string`
Returns the display name shown in the page builder block selection menu.

```php
public function getName(): string
{
    return 'My Custom Block';
    // Or use translations:
    // return 'app.blocks.custom.name';
}
```

#### `getIcon(): string|array`
Returns HTML markup or image URL for the block icon in the selection menu.

```php
// Font icon
public function getIcon(): string
{
    return '<i class="fa fa-star"></i>';
}

// Image
public function getIcon(): string
{
    return '<img src="/assets/images/block-icon.png" alt="Block">';
}

// Using asset system
public function getIcon(): string
{
    return '<img src="' . $this->getPackages()->getUrl('dist/icon.png', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
}
```

#### `getFrontEndTemplatePath(): string`
Returns the path to the Twig template that renders the block on the front-end.

```php
public function getFrontEndTemplatePath(): string
{
    return '@App/front/blocks/my_block.html.twig';
}
```

### Optional Methods

#### `getTab(): string`
Groups blocks in categories in the selection menu.

```php
public function getTab(): string
{
    return 'sylius_happy_cms.blocks.tabs.text_blocks';
    // Other common tabs:
    // - sylius_happy_cms.blocks.tabs.media_blocks
    // - sylius_happy_cms.blocks.tabs.layout_blocks
    // - sylius_happy_cms.blocks.tabs.shared_blocks
}
```

#### `getPosition(): int`
Controls order in block selection menu (lower numbers appear first).

```php
public function getPosition(): int
{
    return 100; // Default position
}
```

#### `supports(?ResourceInterface $resource = null): bool`
Determines if block can be used with specific resource types.

```php
public function supports(?ContentEditableInterface $resource = null): bool
{
    // Only allow this block on Blog entities
    return $resource instanceof BlogPost;
}
```

## Assets Management

### Frontend Assets

Declare JavaScript and CSS files that the block needs on the front-end.

```php
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;

public function configureAssets(): array
{
    return [
        'js' => [
            Asset::new('accordion-block.js')->package(AssetHappyCMSPackage::PACKAGE_NAME),
            'https://cdn.example.com/slider.js', // External script
        ],
        'css' => [
            Asset::new('accordion-block.css')->package(AssetHappyCMSPackage::PACKAGE_NAME),
        ],
        'webpack' => [
            'my-custom-bundle.js',
        ],
    ];
}
```

### Admin Assets

Declare assets needed in the page builder admin interface.

```php
public function configureAdminAssets(): array
{
    return [
        'js' => [
            'https://cdn.example.com/admin-specific-for-my-block.js',
        ],
        'css' => [
            'build/admin/specific-for-my-block.css',
        ],
    ];
}
```

### Form Themes

Customize how the block form appears in the admin.

```php
public function configureAdminFormThemes(): array
{
    return [
        '@App/admin/form/custom_block_theme.html.twig',
    ];
}
```

## Default Blocks

The plugin includes several default blocks:

### WysiwygBlockType
Rich text editor block for general content.

**Fields**:
- `content`: WYSIWYG editor

**Template**: `@SyliusHappyCMSPlugin/front/blocks/wysiwyg_block.html.twig`

### AccordionBlockType
Expandable/collapsible accordion for FAQs and structured content.

**Fields**:
- `title`: Main title
- `wysiwyg`: Description
- `items`: Collection of accordion items
- `cta`: Call-to-action button

**Template**: `@SyliusHappyCMSPlugin/front/blocks/accordion_block.html.twig`

### CtaBlockType
Call-to-action button block.

**Fields**:
- `title`: CTA title
- `description`: Description text
- `button`: Button configuration

**Template**: `@SyliusHappyCMSPlugin/front/blocks/cta_block.html.twig`

### GalleryBlockType
Image gallery block.

**Fields**:
- `title`: Gallery title
- `images`: Collection of images

**Template**: `@SyliusHappyCMSPlugin/front/blocks/gallery_block.html.twig`

### TextCtaBlockType
Combined text and CTA button.

**Fields**:
- `title`: Section title
- `content`: Text content
- `cta`: Button configuration

**Template**: `@SyliusHappyCMSPlugin/front/blocks/text_cta_block.html.twig`

### TextImageCtaBlockType
Text, image, and CTA combined.

**Fields**:
- `title`: Section title
- `content`: Text content
- `image`: Image field
- `cta`: Button configuration

**Template**: `@SyliusHappyCMSPlugin/front/blocks/text_image_cta_block.html.twig`

### KeyFeaturesBlockType
Showcase key features with icons.

**Fields**:
- `title`: Section title
- `features`: Collection of features

**Template**: `@SyliusHappyCMSPlugin/front/blocks/key_features_block.html.twig`

### SharedBlockType
Reference to reusable shared blocks.

**Template**: `@SyliusHappyCMSPlugin/front/blocks/shared_block.html.twig`

### SeoBlockType
SEO metadata block (typically used internally).

**Template**: `@SyliusHappyCMSPlugin/front/blocks/seo_block.html.twig`

## Overriding Blocks

### Step 1: Copy Default Block

Copy the block you want to override from:
```
vendor/agence-adeliom/sylius-happy-cms-plugin/src/Block/AccordionBlockType.php
```

To your project:
```
src/Block/AccordionBlockType.php
```

### Step 2: Modify Namespace

```php
<?php

declare(strict_types=1);

// Change namespace from:
// namespace Adeliom\SyliusHappyCMSPlugin\Block;

// To:
namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;

class AccordionBlockType extends AbstractBlock
{
    // Your modifications...
}
```

### Step 3: Customize

Modify any methods to change behavior:

```php
public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    // Add your custom fields
    $builder
        ->add('customField', TextType::class, [
            'label' => 'My Custom Field',
        ]);

    // Or call parent and add more fields
    parent::buildBlock($builder, $options);
    $builder->add('extraField', TextType::class);
}

public function getName(): string
{
    return 'Custom Accordion Block';
}

public function getFrontEndTemplatePath(): string
{
    return '@App/front/blocks/custom_accordion.html.twig';
}
```

### Step 4: Register Override

Add to `config/services.yaml`:

```yaml
services:
    # Override default accordion block
    sylius.happy_cms.blocks.accordion_type:
        class: App\Block\AccordionBlockType
        public: true
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@translator'
            - '@form.factory'
            - '@assets.packages'
        tags:
            - 'sylius.happy_cms.block'
            - 'form.type'
```

**Important**: Use the exact service ID of the original block to override it.

### Service ID Mapping

| Block Class | Service ID |
|-------------|------------|
| `AccordionBlockType` | `sylius.happy_cms.blocks.accordion_type` |
| `CtaBlockType` | `sylius.happy_cms.blocks.cta_type` |
| `GalleryBlockType` | `sylius.happy_cms.blocks.gallery_type` |
| `KeyFeaturesBlockType` | `sylius.happy_cms.blocks.key_features_type` |
| `TextCtaBlockType` | `sylius.happy_cms.blocks.text_cta_type` |
| `TextImageCtaBlockType` | `sylius.happy_cms.blocks.text_image_cta_type` |
| `WysiwygBlockType` | `sylius.happy_cms.blocks.wysiwyg_type` |

### Step 5: Clear Cache

```bash
php bin/console cache:clear
```

## Display block on front-end

Full example to render blocks and assets in your page template, checking for both the new content block system and the legacy content field:

```twig
    {% if resource.contentBlocks is defined and resource.contentBlocks|length > 0 %}
        {% if preview is defined and preview %}
            {# In preview mode, get blocks sorted by preview position with preview publish state #}
            {% set contentBlocks = resource.getContentBlocksForPreview(app.request.locale) %}
        {% else %}
            {# In production mode, get only published blocks sorted by position #}
            {% set contentBlocks = resource.getPublishedContentBlocks(app.request.locale) %}
        {% endif %}

        {% for contentBlock in contentBlocks %}
            {{ happy_cms_content_block_render(contentBlock, preview|default(false)) }}
        {% endfor %}
        {{ happy_cms_content_block_assets() }}
    {% endif %}

    {{ happy_cms_shared_block_assets() }}
```

### Rendering assets

Those functions will render all the necessary CSS and JS assets for the blocks used on the page, including both flex and shared blocks.

```twig
{{ happy_cms_content_block_assets() }}
{{ happy_cms_shared_block_assets() }}
```

## Advanced Features

### AI Generation Support

Mark blocks as AI-generatable for automatic content creation:

```php
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;

#[AIGeneratable(
    description: 'A testimonial block with customer feedback',
    useCases: ['customer testimonials', 'reviews', 'social proof'],
    priority: 150,
)]
class TestimonialBlock extends AbstractBlock
{
    // ...
}
```

### Searchable Properties

Define which fields should be searchable:

```php
public static function researchableProperties(): array
{
    return ['author', 'quote', 'position'];
}
```

### Conditional Display

Control when blocks appear based on resource type:

```php
public function supports(?ContentEditableInterface $resource = null): bool
{
    // Only show on Product pages
    return $resource instanceof ProductInterface;
}
```

### Complex Form Fields

Use advanced form types:

```php
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;

public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    $builder
        // Rich text editor
        ->add('content', TinymceBridgeType::class, [
            'label' => 'Content',
        ])
        // Sortable repeatable items
        ->add('items', SortableCollectionType::class, [
            'entry_type' => MyItemType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'allow_drag' => true,
        ]);
}
```

## Best Practices

### 1. Naming Conventions

- Use descriptive, action-oriented names
- Block class names should end with "Block" or "BlockType"
- Template names should match block functionality

```php
✅ TestimonialBlock, HeroSectionBlock, ProductGridBlock
❌ Block1, MyBlock, CustomThing
```

### 2. Translations

Use translation keys for all user-facing text:

```php
public function getName(): string
{
    return 'app.blocks.testimonial.name';
}

$builder->add('author', TextType::class, [
    'label' => 'app.blocks.testimonial.fields.author',
]);
```

Translation file `translations/messages.en.yaml`:
```yaml
app:
    blocks:
        testimonial:
            name: 'Testimonial Block'
            fields:
                author: 'Author Name'
                quote: 'Testimonial Text'
```

### 3. Template Safety

Always check if data exists and provide defaults:

```twig
{% set title = settings.title|default('') %}

{% if title %}
    <h2>{{ title }}</h2>
{% endif %}
```

### 4. Form Validation

Add validation constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

$builder->add('email', EmailType::class, [
    'constraints' => [
        new Assert\NotBlank(),
        new Assert\Email(),
    ],
]);
```

### 5. Performance

- Keep block logic simple
- Avoid heavy database queries in templates
- Use caching for expensive operations
- Lazy-load assets when possible

### 6. Reusability

- Create shared blocks for content used across pages
- Use flex blocks for page-specific content
- Document custom blocks for team members

### 7. Testing

- Test blocks in different contexts
- Verify responsive design
- Check all form field validations
- Test with missing/empty data

### 8. Documentation

Add comments to complex blocks:

```php
/**
 * Product Showcase Block
 *
 * Displays a grid of featured products with filtering options.
 * Requires ProductRepository to be available.
 *
 * @author Your Name
 */
class ProductShowcaseBlock extends AbstractBlock
{
    // ...
}
```

## Troubleshooting

### Block Not Appearing in Admin

1. Check block extends `AbstractBlock` or `AbstractSharedBlockType`
2. Verify class is in correct namespace
3. Clear cache: `php bin/console cache:clear`
4. Check for PHP errors in logs

### Template Not Rendering

1. Verify template path in `getFrontEndTemplatePath()`
2. Check template file exists
3. Ensure proper Twig syntax
4. Clear template cache

### Form Fields Not Saving

1. Check field names match between form and template
2. Verify form builder configuration
3. Clear form cache
4. Check browser console for JavaScript errors

### Assets Not Loading

1. Verify asset paths in `configureAssets()`
2. Check assets are published: `php bin/console assets:install`
3. Clear browser cache
4. Inspect network tab in browser dev tools
