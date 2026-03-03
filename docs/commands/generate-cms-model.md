# Generate CMS Model Command

## Command Reference

```bash
php bin/console make:happy-cms:generate-cms-model [options] [--] <scope> [<entryNamespace> [<entryClassName> [<taxonomyClassName>]]]
```

## Description

Generate Entities with Translations, Repositories and Admin classes for your CMS such as FAQ, Blog, Brand pages etc.

## Arguments

### `scope` (required)
Scope of classes to create (ex: Faq, Blog, Brand)

**Example:** `Blog`, `Faq`, `Brand`

### `entryNamespace` (optional)
Namespace for the scope. If not provided, defaults to the scope value.

**Default:** Same as `scope`
**Example:** `Blog`, `MyCustomNamespace`

### `entryClassName` (optional)
Entity filename name for the main entry.

**Default:** `Entry`
**Example:** `Article`, `Post`, `Brand`

### `taxonomyClassName` (optional)
Taxonomy entity filename name for the scope (only used when taxonomy is enabled).

**Default:** `Taxonomy`
**Example:** `Category`, `Tag`, `Type`

## Options

### `--no-flexible-content`
Disable flexible content blocks for this model.

**Default:** Flexible content is enabled by default
**Usage:** `--no-flexible-content`

### `--no-taxonomy`
Do not generate taxonomy associated resources.

**Default:** Taxonomy is enabled by default
**Usage:** `--no-taxonomy`

### `--no-interaction` / `-n`
Do not ask any interactive question (use all defaults and provided arguments).

## Usage Examples

### Interactive Mode (Default)

```bash
php bin/console make:happy-cms:generate-cms-model
```

The command will prompt you for all required information:
- Scope
- Entry namespace
- Entry class name
- Taxonomy class name
- Whether to use flexible content
- Whether to generate taxonomy

### Non-Interactive Mode with All Defaults

```bash
php bin/console make:happy-cms:generate-cms-model Blog --no-interaction
```

This will create:
- **Namespace:** Blog
- **Entry class:** BlogEntry
- **Taxonomy class:** BlogTaxonomy
- **With flexible content:** Yes
- **With taxonomy:** Yes

### Non-Interactive Mode with Custom Names

```bash
php bin/console make:happy-cms:generate-cms-model Blog Blog Article Category --no-interaction
```

This will create:
- **Namespace:** Blog
- **Entry class:** BlogArticle
- **Taxonomy class:** BlogCategory
- **With flexible content:** Yes
- **With taxonomy:** Yes

### Non-Interactive Mode without Taxonomy

```bash
php bin/console make:happy-cms:generate-cms-model Gallery Gallery Image --no-taxonomy --no-interaction
```

This will create:
- **Namespace:** Gallery
- **Entry class:** GalleryImage
- **No taxonomy resources**
- **With flexible content:** Yes

### Non-Interactive Mode without Flexible Content

```bash
php bin/console make:happy-cms:generate-cms-model SimpleContent --no-flexible-content --no-interaction
```

This will create:
- **Namespace:** SimpleContent
- **Entry class:** SimpleContentEntry
- **Taxonomy class:** SimpleContentTaxonomy
- **Without flexible content blocks**
- **With taxonomy:** Yes

### Complete Custom Example

```bash
php bin/console make:happy-cms:generate-cms-model Cms Faq Entry Category --no-interaction
```

This is your original command that will now work perfectly in non-interactive mode!

### Multiple Disabled Features

```bash
php bin/console make:happy-cms:generate-cms-model Cms Landing Page \
    --no-flexible-content \
    --no-taxonomy \
    --no-interaction
```

This will create a minimal model without flexible content and taxonomy.

## Generated Files

The command generates the following files:

### When Taxonomy is Enabled

```
src/
└── Entity/
    └── HappyCMS/
        └── {Namespace}/
            ├── {EntryClassName}.php              # Main entity
            ├── {EntryClassName}Translation.php   # Main entity translations
            ├── {EntryClassName}ContentBlock.php  # Content blocks for entry
            ├── {TaxonomyClassName}.php           # Taxonomy entity
            ├── {TaxonomyClassName}Translation.php # Taxonomy translations
            └── {TaxonomyClassName}ContentBlock.php # Content blocks for taxonomy
```

### When Taxonomy is Disabled

```
src/
└── Entity/
    └── HappyCMS/
        └── {Namespace}/
            ├── {EntryClassName}.php              # Main entity
            ├── {EntryClassName}Translation.php   # Main entity translations
            └── {EntryClassName}ContentBlock.php  # Content blocks for entry
```

### Additional Generated Files

- **Repository classes** for entities
- **Admin controller classes** for CRUD operations
- **Front controller** for public display
- **Menu listener** for admin navigation

## Post-Generation Steps

After running the command, you need to:

1. **Copy the generated route configuration** to `config/routes.yaml`
2. **Copy the generated resource configuration** to `config/packages/sylius_resources.yaml`
3. **Add the route to the admin menu** in your menu configuration
4. **Clear the cache:**
   ```bash
   php bin/console cache:clear
   ```
5. **Run database migrations:**
   ```bash
   php bin/console doctrine:migrations:diff
   php bin/console doctrine:migrations:migrate
   ```

## Testing the Command

Run the test suite to verify the command works correctly:

```bash
vendor/bin/phpunit tests/Functional/Maker/CMS/MakeHappyCMSTest.php
```

## Troubleshooting

### Command asks questions even with `--no-interaction`

Make sure you provide at least the required `scope` argument:

```bash
# ❌ Wrong - will still prompt
php bin/console make:happy-cms:generate-cms-model --no-interaction

# ✅ Correct - fully non-interactive
php bin/console make:happy-cms:generate-cms-model MyScope --no-interaction
```

### Generated files have unexpected names

The command uses the provided arguments to generate names:
- If you provide `Blog` as scope and `Article` as entryClassName, you'll get `BlogArticle.php`
- To customize, provide explicit values for all arguments

### Features are enabled when I want them disabled

Use the `--no-*` options to disable features:

```bash
# Disable all optional features
php bin/console make:happy-cms:generate-cms-model Simple \
    --no-flexible-content \
    --no-taxonomy \
    --no-interaction
```

## Related Documentation

- [Routable Entities Guide](../agents/routable-entities.md)
- [Quick Start Guide](../agents/quickstart.md)
- [Routing System](../agents/routing.md)
