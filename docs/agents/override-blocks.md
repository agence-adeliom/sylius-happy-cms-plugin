# Overriding Default Blocks - AI Guide

This guide helps AI agents override and customize the default blocks provided by the Sylius Happy CMS Plugin.

## Available Default Blocks

The plugin includes these default blocks that can be overridden:
- `AccordionBlockType` - Collapsible accordion sections
- `CtaBlockType` - Call-to-action button block
- `GalleryBlockType` - Image gallery block
- `KeyFeaturesBlockType` - Key features display
- `SeoBlockType` - SEO metadata block
- `SharedBlockType` - Shared block reference
- `TextCtaBlockType` - Text with call-to-action
- `TextImageCtaBlockType` - Combined text, image, and CTA
- `WysiwygBlockType` - WYSIWYG rich text editor

## Step-by-Step Override Process

### 1. Copy the Default Block

Copy the block you want to override from:
```
vendor/agence-adeliom/sylius-happy-cms-plugin/src/Block/YourBlockName.php
```

To your project:
```
src/Block/YourBlockName.php
```

### 2. Modify the Namespace

Update the namespace in your copied file:

```php
<?php

declare(strict_types=1);

// Change from:
// namespace Adeliom\SyliusHappyCMSPlugin\Block;

// To:
namespace App\Block;

// Keep the same class name and extend the same parent
class AccordionBlockType extends AbstractBlock
{
    // Your modifications here...
}
```

### 3. Customize the Block

Modify any of these methods to change behavior:

```php
class AccordionBlockType extends AbstractBlock
{
    // Change form fields
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        // Add, remove, or modify fields
        $builder
            ->add('custom_field', TextType::class, [
                'label' => 'My Custom Field',
            ]);
    }

    // Change display name
    public function getName(): string
    {
        return 'app.blocks.custom_accordion.name';
    }

    // Change category/tab
    public function getTab(): string
    {
        return 'app.blocks.tabs.custom_category';
    }

    // Change icon
    public function getIcon(): string
    {
        return '<i class="icon-accordion-custom"></i>';
    }

    // Change template path
    public function getFrontEndTemplatePath(): string
    {
        return '@App/front/blocks/custom_accordion.html.twig';
    }
}
```

### 4. Register the Override

Add your overridden block to `config/services.yaml`:

```yaml
services:
    # Override the default accordion block
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

**Important**: The service ID must match the original block's service ID to properly override it.

## Service ID Mapping

Use these service IDs to override default blocks:

| Block Class | Service ID |
|-------------|------------|
| `AccordionBlockType` | `sylius.happy_cms.blocks.accordion_type` |
| `CtaBlockType` | `sylius.happy_cms.blocks.cta_type` |
| `GalleryBlockType` | `sylius.happy_cms.blocks.gallery_type` |
| `KeyFeaturesBlockType` | `sylius.happy_cms.blocks.key_features_type` |
| `SeoBlockType` | `sylius.happy_cms.blocks.seo_type` |
| `SharedBlockType` | `sylius.happy_cms.blocks.shared_type` |
| `TextCtaBlockType` | `sylius.happy_cms.blocks.text_cta_type` |
| `TextImageCtaBlockType` | `sylius.happy_cms.blocks.text_image_cta_type` |
| `WysiwygBlockType` | `sylius.happy_cms.blocks.wysiwyg_type` |

## Common Customization Scenarios

### Scenario 1: Add Extra Fields

```php
public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    // Call parent to keep existing fields
    parent::buildBlock($builder, $options);

    // Add your custom fields
    $builder
        ->add('custom_option', ChoiceType::class, [
            'choices' => [
                'Option 1' => 'option1',
                'Option 2' => 'option2',
            ],
            'label' => 'Custom Option',
        ]);
}
```

### Scenario 2: Change Template

```php
public function getFrontEndTemplatePath(): string
{
    // Use your custom template instead of the default
    return '@App/front/blocks/my_custom_accordion.html.twig';
}
```

Then create: `templates/front/blocks/my_custom_accordion.html.twig`

### Scenario 3: Modify Field Behavior

```php
public function buildBlock(FormBuilderInterface $builder, array $options): void
{
    // Don't call parent, rebuild from scratch
    $builder
        ->add('title', TextType::class, [
            'required' => true,  // Make required
            'label' => 'Custom Title',
            'attr' => [
                'maxlength' => 100,  // Add constraint
            ],
        ])
        ->add('items', CollectionType::class, [
            'entry_type' => AccordionItemType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => false,
            'entry_options' => ['label' => false],
        ]);
}
```

### Scenario 4: Change Block Category

```php
public function getTab(): string
{
    // Move block to a different category in admin UI
    return 'sylius_happy_cms.blocks.tabs.custom_category';
}

public function getName(): string
{
    // Change the display name
    return 'My Enhanced Accordion Block';
}
```

## Template Override

If you only need to change the front-end appearance:

### Option 1: Override Template Path in Block

```php
public function getFrontEndTemplatePath(): string
{
    return '@App/front/blocks/custom_accordion.html.twig';
}
```

### Option 2: Use Twig Template Override

Create a template in your project that mirrors the bundle structure:
```
templates/bundles/SyliusHappyCMSPlugin/front/blocks/accordion.html.twig
```

This automatically overrides the default template without modifying the block class.

## After Overriding

1. **Clear cache**:
```bash
php bin/console cache:clear
```

2. **Verify** the override is working:
   - Check admin page builder shows your modifications
   - Test block rendering on front-end
   - Verify new fields save correctly

3. **Update translations** if you changed label keys:
```yaml
# translations/messages.en.yaml
app:
    blocks:
        custom_accordion:
            name: 'Custom Accordion'
```

## Troubleshooting

### Block Still Shows Old Behavior
- Verify service ID in `services.yaml` matches exactly
- Clear cache: `php bin/console cache:clear`
- Check for typos in namespace or class name

### New Fields Not Saving
- Ensure form fields are properly mapped
- Check entity has corresponding properties
- Verify database schema is updated

### Template Not Rendering
- Check template path is correct
- Verify template file exists
- Ensure proper Twig namespace is used

## Best Practices

1. **Keep Parent Functionality**: Call `parent::buildBlock()` when adding fields
2. **Document Changes**: Add comments explaining why you overrode the block
3. **Test Thoroughly**: Test in both admin and front-end contexts
4. **Version Control**: Track changes for easier updates when plugin updates
5. **Backup Original**: Keep reference to original block implementation
