```markdown
## Sylius Happy CMS Plugin Integration

This file provides guidance to AI agents when working with code with Sylius Happy CMS Plugin.
This project uses **Sylius Happy CMS Plugin** by Adeliom for content management and page building.

### Plugin Documentation Reference

The plugin provides specialized AI guides for common development tasks. When working with CMS features, refer to these guides:

#### Quick Start Guide ⭐ START HERE
**Guide**: `./quickstart.md`

Your first stop for:
- Task-based quick reference
- Standard workflow patterns
- Decision tree for choosing the right approach
- Troubleshooting quick fixes

#### Commands Reference
**Guide**: `.//commands.md`

Complete reference of all available commands including:
- Installation and setup commands
- Entity and block generation commands
- Database and cache management
- Command execution order and workflows
- Troubleshooting commands

#### Creating Routable Entities
**Guide**: `.//routable-entities.md`

Use when creating:
- Blog systems
- FAQ pages
- Brand pages
- Any custom routable CMS content

**Command**: `php bin/console make:happy-cms:generate-cms-model`

#### Creating Content Blocks
**Guide**: `.//blocks.md`

Use when creating:
- Flex blocks (page-specific content)
- Shared blocks (reusable across pages)
- Custom content components

**Commands**:
- `php bin/console make:happy-cms:block` (flex blocks)
- `php bin/console make:happy-cms:block:shared` (shared blocks)

#### Overriding Default Blocks
**Guide**: `.//override-blocks.md`

Use when customizing:
- AccordionBlockType
- CtaBlockType
- GalleryBlockType
- WysiwygBlockType
- And other default blocks

#### Routing System
**Guide**: `.//routing.md`

Understand when:
- Routes are automatically generated
- Customizing URL structure
- Multi-language routing
- HTTP cache and breadcrumbs
- Troubleshooting routing issues

#### Menu System
**Guide**: `.//menu.md`

Use when:
- Creating navigation menus
- Managing hierarchical menu structures
- Rendering menus in templates
- Multi-language menu support
- Performance optimization with ESI

### CMS Development Workflow

When making CMS-related changes:

1. **Identify the task type**:
   - New routable entity → Use `make:happy-cms:generate-cms-model`
   - New content block → Use `make:happy-cms:block` or `make:happy-cms:block:shared`
   - Customize existing block → Follow override process

2. **Generate/Modify code**:
   - Use maker commands for new entities/blocks
   - Follow plugin conventions for manual creation
   - Reference the appropriate AI guide

3. **Post-generation steps**:
   ```bash
   # Always clear cache after changes
   php bin/console cache:clear

   # Update database if entity changes
   php bin/console doctrine:migrations:diff
   php bin/console doctrine:migrations:migrate
   ```

4. **Testing**:
   - Verify in admin interface
   - Test on front-end
   - Check translations if using translation keys

### Important File Locations

```
src/
├── Entity/           # Custom routable entities
├── Repository/       # Entity repositories
├── Admin/           # Admin CRUD classes
└── Block/           # Custom blocks

config/
├── routes.yaml                        # Route definitions
├── packages/
    ├── sylius_resource.yaml          # Resource configuration
    └── sylius_happy_cms.yaml         # CMS configuration

templates/
├── bundles/
│   └── SyliusHappyCMSPlugin/        # Template overrides
└── front/
    └── blocks/                       # Custom block templates
```

### Common Commands

```bash
# CMS Entity & Block Generation
php bin/console make:happy-cms:generate-cms-model
php bin/console make:happy-cms:block
php bin/console make:happy-cms:block:shared

# Database Management
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Cache Management
php bin/console cache:clear

# Asset Management
php bin/console assets:install
```

### Best Practices

1. **Always use maker commands** when available for consistency
2. **Clear cache** after entity/block changes
3. **Use translation keys** for multilingual support
4. **Follow naming conventions** established by the plugin
5. **Test in both admin and front-end** contexts

### Troubleshooting

If you encounter issues:
1. Check the relevant guide in `docs/agents/` directory of the plugin
2. Verify cache is cleared
3. Ensure database schema is up to date
4. Check service registration in `config/services.yaml`
5. Review plugin documentation in the plugin directory vendor/agence-adeliom/sylius-happy-cms-plugin/ or '../../readme.md'
```
