# Content Blocks System - AI Guide

This guide helps AI agents understand and work with the content block system in Sylius Happy CMS Plugin.

## Overview

The plugin provides a flexible block system for creating reusable content components that can be added to CMS pages through a visual page builder interface.

##Block Types

### Flex Blocks

**Purpose**: Page-specific content blocks that are unique to each page.

**Location**: Extend `src/Factory/Block/AbstractBlock.php`

**When to use**:
- Unique hero sections
- Page-specific content
- One-time promotional elements
- Custom layouts for individual pages

**Auto-registration**: Automatically registered when extending `AbstractBlock`

### Shared Blocks

**Purpose**: Reusable content blocks across multiple pages.

**Location**: Extend `src/Factory/SharedBlock/AbstractSharedBlockType.php`

**When to use**:
- Newsletter signup forms
- Contact information
- Social media feeds
- Global CTA elements
- Consistent components

**Auto-registration**: Automatically registered when extending `AbstractSharedBlockType`

## Creating Blocks

### Using Commands

```bash
# Generate flex block
php bin/console make:happy-cms:block

# Generate shared block
php bin/console make:happy-cms:block:shared
```

Both commands:
1. Ask for block name
2. Create block class in `src/Block/`
3. Create template in `templates/front/blocks/`
4. Auto-register the block

### Manual Creation - Flex Block

**File**: `src/Block/TestimonialBlock.php`

```php
<?php

declare(strict_types=1);

namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class TestimonialBlock extends AbstractBlock
{
    /**
     * Build block configuration form
     * Fields appear in page builder admin
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
     * Block name in selection menu
     */
    public function getName(): string
    {
        return 'Testimonial Block';
    }

    /**
     * Category/tab in selection menu
     */
    public function getTab(): string
    {
        return 'sylius_happy_cms.blocks.tabs.text_blocks';
    }

    /**
     * Icon in selection menu
     */
    public function getIcon(): string
    {
        return '<i class="icon-quote"></i>';
    }

    /**
     * Front-end template path
     */
    public function getFrontEndTemplatePath(): string
    {
        return '@App/front/blocks/testimonial_block.html.twig';
    }

    /**
     * Position in menu (lower = higher)
     */
    public function getPosition(): int
    {
        return 50;
    }
}
```

### Manual Creation - Shared Block

**File**: `src/Block/NewsletterBlock.php`

```php
<?php

declare(strict_types=1);

namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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

## Block Interface Methods

### Required Methods

#### `getName(): string`
Display name in block selection menu.

```php
return 'My Custom Block';
// Or with translation:
return 'app.blocks.custom.name';
```

#### `getIcon(): string|array`
Icon HTML or image URL in selection menu.

```php
// Font icon
return '<i class="fa fa-star"></i>';

// Image
return '<img src="/path/to/icon.png" alt="Icon">';

// Using asset system
return '<img src="' . $this->getPackages()->getUrl('dist/icon.png', AssetHappyCMSPackage::PACKAGE_NAME) . '" alt="">';
```

#### `getFrontEndTemplatePath(): string`
Twig template path for rendering.

```php
return '@App/front/blocks/my_block.html.twig';
```

### Optional Methods

#### `getTab(): string`
Category for grouping in selection menu.

```php
// Common tabs:
return 'sylius_happy_cms.blocks.tabs.text_blocks';
return 'sylius_happy_cms.blocks.tabs.media_blocks';
return 'sylius_happy_cms.blocks.tabs.layout_blocks';
return 'sylius_happy_cms.blocks.tabs.shared_blocks';
```

#### `getPosition(): int`
Sort order (lower = first).

```php
return 100; // Default
```

#### `supports(?ResourceInterface $resource = null): bool`
Condition to show block.

```php
// Only on specific entity
return $resource instanceof BlogPost;

// Always show
return true;
```

## Creating Templates

Block data available via `settings` variable in templates.

**Template**: `templates/front/blocks/testimonial_block.html.twig`

```twig
{% set author = settings.author|default('') %}
{% set position = settings.position|default('') %}
{% set quote = settings.quote|default('') %}
{% set rating = settings.rating|default(0) %}

{% if author and quote %}
<div class="testimonial-block">
    <blockquote class="quote">
        "{{ quote }}"
    </blockquote>

    {% if rating > 0 %}
        <div class="rating">
            {% for i in 1..rating %}
                <span class="star">★</span>
            {% endfor %}
        </div>
    {% endif %}

    <div class="author">
        <p class="name">{{ author }}</p>
        {% if position %}
            <p class="position">{{ position }}</p>
        {% endif %}
    </div>
</div>
{% endif %}
```

### Template Data Access

```twig
{# Simple field #}
{{ settings.fieldName }}

{# With default #}
{{ settings.fieldName|default('Default') }}

{# Check existence #}
{% if settings.fieldName is defined %}
    {{ settings.fieldName }}
{% endif %}

{# Rich text #}
{{ settings.content|raw }}

{# Collections #}
{% for item in settings.items|default([]) %}
    {{ item.title }}
{% endfor %}
```

## Assets Configuration

### Frontend Assets

```php
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;

public function configureAssets(): array
{
    return [
        'js' => [
            Asset::new('block-script.js')->package(AssetHappyCMSPackage::PACKAGE_NAME),
            'https://cdn.example.com/library.js',
        ],
        'css' => [
            Asset::new('block-styles.css')->package(AssetHappyCMSPackage::PACKAGE_NAME),
        ],
        'webpack' => [
            'custom-bundle.js',
        ],
    ];
}
```

### Admin Assets

```php
public function configureAdminAssets(): array
{
    return [
        'js' => ['admin/preview.js'],
        'css' => ['admin/preview.css'],
    ];
}
```

### Form Themes

```php
public function configureAdminFormThemes(): array
{
    return [
        '@App/admin/form/custom_theme.html.twig',
    ];
}
```

## Default Blocks Reference

### WysiwygBlockType
Rich text editor block.

**Class**: `src/Block/WysiwygBlockType.php`
**Fields**: `content` (WYSIWYG)
**Template**: `@SyliusHappyCMSPlugin/front/blocks/wysiwyg_block.html.twig`

### AccordionBlockType
Expandable/collapsible accordion.

**Class**: `src/Block/AccordionBlockType.php`
**Fields**: `title`, `wysiwyg`, `items`, `cta`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/accordion_block.html.twig`

### CtaBlockType
Call-to-action button.

**Class**: `src/Block/CtaBlockType.php`
**Fields**: `title`, `description`, `button`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/cta_block.html.twig`

### GalleryBlockType
Image gallery.

**Class**: `src/Block/GalleryBlockType.php`
**Fields**: `title`, `images`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/gallery_block.html.twig`

### TextCtaBlockType
Text with CTA button.

**Class**: `src/Block/TextCtaBlockType.php`
**Fields**: `title`, `content`, `cta`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/text_cta_block.html.twig`

### TextImageCtaBlockType
Text, image, and CTA.

**Class**: `src/Block/TextImageCtaBlockType.php`
**Fields**: `title`, `content`, `image`, `cta`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/text_image_cta_block.html.twig`

### KeyFeaturesBlockType
Feature showcase with icons.

**Class**: `src/Block/KeyFeaturesBlockType.php`
**Fields**: `title`, `features`
**Template**: `@SyliusHappyCMSPlugin/front/blocks/key_features_block.html.twig`

## Overriding Blocks

### Step 1: Copy Block

Copy from `vendor/agence-adeliom/sylius-happy-cms-plugin/src/Block/` to `src/Block/`

### Step 2: Update Namespace

```php
<?php

declare(strict_types=1);

// Change from:
// namespace Adeliom\SyliusHappyCMSPlugin\Block;

// To:
namespace App\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;

class AccordionBlockType extends AbstractBlock
{
    // Modifications...
}
```

### Step 3: Customize

```php
public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    // Option 1: Replace completely
    $builder
        ->add('customField', TextType::class);

    // Option 2: Extend parent
    parent::buildBlock($builder, $options);
    $builder->add('extraField', TextType::class);
}

public function getName(): string
{
    return 'Custom Accordion';
}

public function getFrontEndTemplatePath(): string
{
    return '@App/front/blocks/custom_accordion.html.twig';
}
```

### Step 4: Register Service

`config/services.yaml`:

```yaml
services:
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

**Service ID mapping**:
- `AccordionBlockType` → `sylius.happy_cms.blocks.accordion_type`
- `CtaBlockType` → `sylius.happy_cms.blocks.cta_type`
- `GalleryBlockType` → `sylius.happy_cms.blocks.gallery_type`
- `WysiwygBlockType` → `sylius.happy_cms.blocks.wysiwyg_type`

### Step 5: Clear Cache

```bash
php bin/console cache:clear
```

## Advanced Features

### AI Generation Attribute

```php
use Adeliom\SyliusHappyCMSPlugin\Attribute\AIGeneratable;

#[AIGeneratable(
    description: 'Customer testimonial block with rating',
    useCases: ['testimonials', 'reviews', 'social proof'],
    priority: 150,
)]
class TestimonialBlock extends AbstractBlock
{
    // ...
}
```

### Searchable Fields

```php
public static function researchableProperties(): array
{
    return ['author', 'quote', 'position'];
}
```

### Complex Form Types

```php
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusHappyCMSPlugin\Form\TinymceBridgeType;

public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    $builder
        // Rich text
        ->add('content', TinymceBridgeType::class, [
            'label' => 'Content',
        ])
        // Repeatable sortable items
        ->add('items', SortableCollectionType::class, [
            'entry_type' => ItemType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'allow_drag' => true,
        ]);
}
```

### Form Validation

```php
use Symfony\Component\Validator\Constraints as Assert;

$builder->add('email', EmailType::class, [
    'constraints' => [
        new Assert\NotBlank(),
        new Assert\Email(),
    ],
]);
```

## Common Patterns

### Block with Collection

```php
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

$builder->add('items', CollectionType::class, [
    'entry_type' => TextType::class,
    'entry_options' => ['label' => false],
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
]);
```

Template:
```twig
{% for item in settings.items|default([]) %}
    <div>{{ item }}</div>
{% endfor %}
```

### Block with Embedded Type

```php
use App\Form\ButtonType;

$builder->add('button', ButtonType::class, [
    'label' => 'Button Configuration',
]);
```

### Block with Media

```php
use Adeliom\EasyMediaBundle\Form\EasyMediaType;

$builder->add('image', EasyMediaType::class, [
    'label' => 'Image',
    'required' => false,
]);
```

Template:
```twig
{% if settings.image %}
    <img src="{{ settings.image.url }}" alt="{{ settings.image.name }}">
{% endif %}
```

## Best Practices

1. **Naming**: Use descriptive names ending with "Block" or "BlockType"
2. **Translations**: Use translation keys for all labels
3. **Defaults**: Always provide default values in templates
4. **Validation**: Add constraints for required fields
5. **Safety**: Check data exists before using in templates
6. **Performance**: Keep block logic simple, avoid heavy queries
7. **Reusability**: Use shared blocks for repeated content
8. **Documentation**: Add PHPDoc comments for complex blocks

## Troubleshooting

### Block Not Appearing

**Check**:
1. Block extends correct parent class
2. Class in correct namespace (`App\Block`)
3. Cache cleared
4. No PHP errors in logs

**Solution**:
```bash
php bin/console cache:clear
tail -f var/log/dev.log
```

### Template Not Rendering

**Check**:
1. Template path correct in `getFrontEndTemplatePath()`
2. Template file exists
3. Twig syntax valid
4. Template cache cleared

**Debug**:
```twig
{# Add to template #}
{{ dump(settings) }}
```

### Form Fields Not Saving

**Check**:
1. Field names match form builder
2. Form configuration correct
3. Browser console for JS errors
4. Network tab for failed requests

### Assets Not Loading

**Check**:
1. Asset paths correct in `configureAssets()`
2. Assets published: `php bin/console assets:install`
3. Browser cache cleared
4. Network tab in dev tools

## File Structure

```
src/
└── Block/
    ├── TestimonialBlock.php
    ├── NewsletterBlock.php
    └── HeroBlock.php

templates/
└── front/
    └── blocks/
        ├── testimonial_block.html.twig
        ├── newsletter_block.html.twig
        └── hero_block.html.twig

config/
└── services.yaml  # Block overrides
```

## Available Form Types

Common Symfony form types:
- `TextType` - Single line text
- `TextareaType` - Multi-line text
- `IntegerType` - Integer number
- `NumberType` - Decimal number
- `CheckboxType` - Boolean checkbox
- `ChoiceType` - Select/Radio/Checkbox group
- `CollectionType` - Repeatable fields
- `DateType` / `DateTimeType` - Date pickers

Plugin form types:
- `TinymceBridgeType` - WYSIWYG editor
- `SortableCollectionType` - Sortable repeatable fields
- `ButtonEmbeddableType` - Button configuration
- `EasyMediaType` - Media picker

## Database Schema

Blocks are stored as JSON in content pages:
- `content_blocks` field in routable entities
- JSON array of block configurations
- Each block has: `block_type`, `block_published`, `position`, and field data
