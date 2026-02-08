# Available Commands - AI Guide

Complete reference of all Sylius Happy CMS Plugin commands for AI agents.

## Installation Commands

### make:happy-cms:install
**Purpose**: Initial plugin setup and file generation

```bash
php bin/console make:happy-cms:install
```

**What it does**:
- Creates default entities in `src/Entity/`
- Creates repositories in `src/Repository/`
- Creates admin CRUD classes in `src/Admin/`
- Updates `config/routes.yaml`
- Updates `config/packages/sylius_resource.yaml`
- Updates `config/packages/sylius_happy_cms.yaml`

**When to use**:
- First-time plugin installation
- Resetting to default configuration

**Post-execution**:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

## Entity Generation Commands

### make:happy-cms:generate-cms-model
**Purpose**: Create custom routable CMS entities

```bash
php bin/console make:happy-cms:generate-cms-model
```

**Interactive prompts**:
1. Resource name (e.g., BlogPost, Faq, Brand)
2. Enable translations? (Yes/No)
3. Enable SEO features? (Yes/No)
4. Additional options...

**Generated files**:
- `src/Entity/{ResourceName}.php`
- `src/Entity/{ResourceName}Translation.php` (if translatable)
- `src/Repository/{ResourceName}Repository.php`
- `src/Admin/{ResourceName}Crud.php`

**Automatic updates**:
- Routes configuration
- Sylius resource configuration
- CMS configuration

**Use cases**:
- Blog systems
- FAQ pages
- Brand showcase pages
- Product landing pages
- Custom content types

**Example workflow**:
```bash
# Generate entity
php bin/console make:happy-cms:generate-cms-model

# Update database
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear

# Verify in admin at /admin/your-resource
```

## Block Generation Commands

### make:happy-cms:block
**Purpose**: Create flex content blocks (page-specific)

```bash
php bin/console make:happy-cms:block
```

**Interactive prompts**:
1. Block name (e.g., HeroBlock, TestimonialBlock)
2. Block category/tab
3. Form fields configuration

**Generated files**:
- `src/Block/{BlockName}.php`
- `templates/front/blocks/{block_name}.html.twig`

**When to use**:
- Creating page-specific content components
- Custom layouts for individual pages
- Non-reusable content blocks

**Example**:
```bash
php bin/console make:happy-cms:block
# Enter: HeroBlock
# Result: src/Block/HeroBlock.php + template
php bin/console cache:clear
```

### make:happy-cms:block:shared
**Purpose**: Create shared content blocks (reusable)

```bash
php bin/console make:happy-cms:block:shared
```

**Interactive prompts**:
1. Block name (e.g., NewsletterBlock, ContactFormBlock)
2. Block category/tab
3. Form fields configuration

**Generated files**:
- `src/Block/{BlockName}.php`
- `templates/front/blocks/{block_name}.html.twig`

**When to use**:
- Creating reusable content across multiple pages
- Consistent components (newsletter forms, CTAs)
- Centrally managed content

**Difference from flex blocks**:
- Shared blocks: One instance, many pages
- Flex blocks: Unique instance per page

**Example**:
```bash
php bin/console make:happy-cms:block:shared
# Enter: NewsletterBlock
# Result: Reusable newsletter signup block
php bin/console cache:clear
```

## Demo Content Commands

### make:happy-cms:demo-content
**Purpose**: Generate sample CMS content for testing

```bash
php bin/console make:happy-cms:demo-content
```

**What it generates**:
- Sample pages with content blocks
- Example menus
- Media files
- Shared blocks

**When to use**:
- Development environment setup
- Testing new features
- Demonstrating plugin capabilities
- Learning plugin structure

**Warning**: Only use in development environments!

## Standard Symfony Commands (Related to CMS)

### Database Management

```bash
# Create migration after entity changes
php bin/console doctrine:migrations:diff

# Execute migrations
php bin/console doctrine:migrations:migrate

# Check migration status
php bin/console doctrine:migrations:status

# Rollback last migration
php bin/console doctrine:migrations:migrate prev
```

### Cache Management

```bash
# Clear all cache
php bin/console cache:clear

# Warmup cache
php bin/console cache:warmup

# Clear specific cache pools
php bin/console cache:pool:clear cache.app
```

### Asset Management

```bash
# Install assets to public directory
php bin/console assets:install

# Install with symlinks (development)
php bin/console assets:install --symlink

# Compile frontend assets (if using Webpack Encore)
yarn encore dev
yarn encore production
```

### Debugging & Information

```bash
# List all routes (including CMS routes)
php bin/console debug:router

# Find CMS routes
php bin/console debug:router | grep happy_cms

# List all services
php bin/console debug:container

# Find CMS services
php bin/console debug:container | grep happy_cms

# Check entity mapping
php bin/console doctrine:mapping:info
```

## Command Execution Order

### For New Installation
```bash
1. composer require agence-adeliom/sylius-happy-cms-plugin
2. # Configure bundles.php, routes, config files
3. php bin/console make:happy-cms:install
4. php bin/console doctrine:migrations:diff
5. php bin/console doctrine:migrations:migrate
6. php bin/console assets:install
7. php bin/console cache:clear
```

### For Adding Routable Entity
```bash
1. php bin/console make:happy-cms:generate-cms-model
2. php bin/console doctrine:migrations:diff
3. php bin/console doctrine:migrations:migrate
4. php bin/console cache:clear
5. # Test in admin interface
```

### For Adding Block
```bash
1. php bin/console make:happy-cms:block  # or block:shared
2. php bin/console cache:clear
3. # Verify in page builder
4. # Test front-end rendering
```

### For Overriding Block
```bash
1. # Copy block from vendor to src/Block/
2. # Modify the block class
3. # Register in config/services.yaml
4. php bin/console cache:clear
5. # Test in page builder
```

## Common Command Patterns

### After Code Generation
Always run after generating entities or blocks:
```bash
php bin/console cache:clear
```

### After Entity Changes
Always run after modifying entities:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Development Workflow
```bash
# Make changes
php bin/console cache:clear

# Test
# If entity changed:
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Verify
php bin/console debug:router | grep your_entity
```

## Troubleshooting Commands

### Cache Issues
```bash
php bin/console cache:clear --no-warmup
rm -rf var/cache/*
php bin/console cache:warmup
```

### Database Sync Issues
```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql
```

### Asset Issues
```bash
rm -rf public/bundles/*
php bin/console assets:install --symlink
```

## Environment-Specific Commands

### Development
```bash
php bin/console cache:clear --env=dev
php bin/console make:happy-cms:demo-content
```

### Production
```bash
php bin/console cache:clear --env=prod --no-debug
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console assets:install --no-debug
```

### Testing
```bash
php bin/console cache:clear --env=test
php bin/console doctrine:migrations:migrate --env=test
```

## Command Options Reference

### Common Options
- `--help` - Display command help
- `--quiet` - Suppress output
- `--verbose` (-v, -vv, -vvv) - Increase verbosity
- `--env=ENV` - Specify environment
- `--no-interaction` - Non-interactive mode

### Doctrine Options
- `--force` - Force execution (use carefully!)
- `--dump-sql` - Show SQL without executing
- `--no-interaction` - Skip confirmation prompts

## Best Practices

1. **Always clear cache** after generating entities or blocks
2. **Run migrations** after entity changes
3. **Use --help** to understand command options
4. **Test in development** before production deployment
5. **Backup database** before destructive operations
6. **Use --dump-sql** to preview database changes
7. **Check command output** for errors or warnings
