# Creating Routable Entities - AI Guide

This guide helps AI agents create routable entities (custom CMS resources) in the Sylius Happy CMS Plugin.

## Quick Command

```bash
php bin/console make:happy-cms:generate-cms-model
```

## What This Command Generates

The command will interactively create:
1. **Entity** - Symfony/Doctrine entity with routable traits
2. **Repository** - Doctrine repository for the entity
3. **Admin CRUD** - Complete admin interface for managing the resource
4. **Front-end routes** - Public routes to display the resource
5. **Controllers** - Admin and shop controllers
6. **CMS integration** - Page builder and content management features

## Automatic Configuration Updates

The command automatically updates:
- `config/routes.yaml` - Adds proper routing declarations
- `config/packages/sylius_resource.yaml` - Registers the Sylius resource
- `config/packages/sylius_happy_cms.yaml` - Adds CMS configuration

## Common Use Cases

### Example 1: Blog System
```bash
php bin/console make:happy-cms:generate-cms-model
# When prompted:
# - Resource name: BlogPost
# - Translatable: Yes
# - SEO: Yes
```

### Example 2: FAQ Pages
```bash
php bin/console make:happy-cms:generate-cms-model
# When prompted:
# - Resource name: Faq
# - Translatable: Yes
# - SEO: Yes
```

### Example 3: Brand Pages
```bash
php bin/console make:happy-cms:generate-cms-model
# When prompted:
# - Resource name: Brand
# - Translatable: Yes
# - SEO: Yes
```

## Generated Files Structure

After running the command, you'll have:
```
src/
├── Entity/
│   └── YourResource.php
├── Repository/
│   └── YourResourceRepository.php
└── Admin/
    └── YourResourceCrud.php
```

## Post-Generation Steps

1. **Update database schema**:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

2. **Clear cache**:
```bash
php bin/console cache:clear
```

3. **Verify configuration** - Check that routes and resources are properly registered

## Key Traits and Interfaces

Generated entities typically include:
- `RoutableTrait` - Enables URL routing
- `SeoTrait` - SEO metadata (title, description, keywords)
- `TranslatableTrait` - Multi-language support
- `ContentBlockTrait` - Page builder integration

## Troubleshooting

If the command fails:
1. Check write permissions in `src/`, `config/` directories
2. Verify symfony/maker-bundle is installed
3. Ensure database connection is configured
4. Review manual configuration guide: `docs/DETAILED_CONFIG.md`
