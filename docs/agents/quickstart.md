# Quick Start Guide for AI Agents

This is a quick reference guide for AI agents working with the Sylius Happy CMS Plugin. Use this as your first stop for common tasks.

## 🎯 Common Tasks Quick Reference

### I need to create a new type of CMS page (Blog, FAQ, Brand, etc.)
→ **Use**: [Routable Entities Guide](./routable-entities.md)
```bash
php bin/console make:happy-cms:generate-cms-model
```

### I need to create a custom content block
→ **Use**: [Content Blocks Guide](./blocks.md)
```bash
php bin/console make:happy-cms:block          # For page-specific blocks
php bin/console make:happy-cms:block:shared   # For reusable blocks
```

### I need to customize an existing block (Accordion, CTA, Gallery, etc.)
→ **Use**: [Override Blocks Guide](./override-blocks.md)
1. Copy block from `vendor/.../src/Block/` to `src/Block/`
2. Modify the class
3. Register in `config/services.yaml`

### I need to know what commands are available
→ **Use**: [Commands Reference](./commands.md)
Complete list of all CMS commands with examples

### I need to understand how routing works
→ **Use**: [Routing System Guide](./routing.md)
Learn about automatic route generation, URL hierarchy, and customization

### I need to create or manage navigation menus
→ **Use**: [Menu System Guide](./menu.md)
Learn about creating hierarchical menus, templates, and rendering

## 📋 Standard Workflow Patterns

### Creating New Routable Entity
```bash
# 1. Generate entity
php bin/console make:happy-cms:generate-cms-model

# 2. Update database
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# 3. Clear cache
php bin/console cache:clear

# 4. Test in admin at /admin/{resource-name}
```

### Creating New Block
```bash
# 1. Generate block
php bin/console make:happy-cms:block  # or block:shared

# 2. Clear cache
php bin/console cache:clear

# 3. Create template in templates/front/blocks/

# 4. Test in page builder
```

### Overriding Existing Block
```bash
# 1. Copy from vendor to src/Block/
# 2. Update namespace to App\Block
# 3. Modify as needed
# 4. Register in config/services.yaml
# 5. Clear cache
php bin/console cache:clear
```

## 🔑 Key Concepts

### Routable Entities
- **What**: Custom entity types that have their own URLs (Blog posts, FAQ pages, etc.)
- **Command**: `make:happy-cms:generate-cms-model`
- **Generated**: Entity, Repository, Admin CRUD, Routes, Controllers
- **Use for**: New content types that need URLs

### Flex Blocks
- **What**: Content blocks unique to each page
- **Command**: `make:happy-cms:block`
- **Use for**: Page-specific content that won't be reused

### Shared Blocks
- **What**: Reusable content blocks across multiple pages
- **Command**: `make:happy-cms:block:shared`
- **Use for**: Content that appears on multiple pages (newsletter forms, contact sections)

## 📁 Important File Locations

```
src/
├── Entity/          # Routable entities (Blog, FAQ, etc.)
├── Repository/      # Doctrine repositories
├── Admin/          # Admin CRUD classes
└── Block/          # Custom blocks

config/
├── routes.yaml                      # Route definitions
├── packages/
    ├── sylius_resource.yaml        # Sylius resource config
    └── sylius_happy_cms.yaml       # CMS configuration

templates/
├── bundles/
│   └── SyliusHappyCMSPlugin/      # Override plugin templates
└── front/
    └── blocks/                     # Custom block templates
```

## ⚡ After Every Change Checklist

### After generating entity or block:
- [ ] `php bin/console cache:clear`

### After modifying entity:
- [ ] `php bin/console doctrine:migrations:diff`
- [ ] `php bin/console doctrine:migrations:migrate`
- [ ] `php bin/console cache:clear`

### After overriding block:
- [ ] Verify service registration in `config/services.yaml`
- [ ] `php bin/console cache:clear`
- [ ] Test in admin page builder
- [ ] Test front-end rendering

## 🚨 Troubleshooting Quick Fixes

### Block not appearing in admin
```bash
php bin/console cache:clear --no-warmup
rm -rf var/cache/*
php bin/console cache:warmup
```

### Entity not recognized
```bash
php bin/console debug:router | grep entity_name
php bin/console doctrine:mapping:info
```

### Template not rendering
- Check template path in block's `getFrontEndTemplatePath()`
- Verify template file exists
- Clear cache

### Database out of sync
```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 📚 Detailed Guides

For comprehensive information, refer to:

1. **[commands.md](./commands.md)** - All available commands with detailed explanations
2. **[routable-entities.md](./routable-entities.md)** - Complete guide to creating CMS pages
3. **[blocks.md](./blocks.md)** - Creating and managing content blocks
4. **[override-blocks.md](./override-blocks.md)** - Customizing default blocks
5. **[routing.md](./routing.md)** - Understanding the dynamic routing system
6. **[menu.md](./menu.md)** - Creating and managing navigation menus
7. **[README.md](./README.md)** - Guide organization and integration instructions

## 🎓 Learning Path for New AI Agents

1. **Start here**: Read this quickstart
2. **Understand commands**: Review [commands.md](./commands.md)
3. **Learn entities**: Read [routable-entities.md](./routable-entities.md)
4. **Learn blocks**: Read [blocks.md](./blocks.md)
5. **Understand routing**: Read [routing.md](./routing.md)
6. **Understand menus**: Read [menu.md](./menu.md)
7. **Advanced**: Study [override-blocks.md](./override-blocks.md)

## 💡 Best Practices

1. ✅ Always use maker commands when available
2. ✅ Clear cache after code generation
3. ✅ Run database migrations after entity changes
4. ✅ Test in both admin and front-end
5. ✅ Use translation keys for multi-language support
6. ✅ Follow existing naming conventions
7. ❌ Don't skip cache clearing
8. ❌ Don't forget database migrations
9. ❌ Don't hardcode text (use translations)

## 🔄 Quick Decision Tree

```
Need to add CMS functionality?
│
├─ New content type with URLs?
│  └─ Use: make:happy-cms:generate-cms-model
│
├─ New content block?
│  ├─ Used on one page only?
│  │  └─ Use: make:happy-cms:block
│  └─ Used on multiple pages?
│     └─ Use: make:happy-cms:block:shared
│
└─ Customize existing block?
   └─ Follow: override-blocks.md guide
```

## 📞 Need More Help?

- **Commands**: See [commands.md](./commands.md)
- **Entities**: See [routable-entities.md](./routable-entities.md)
- **Blocks**: See [blocks.md](./blocks.md)
- **Overrides**: See [override-blocks.md](./override-blocks.md)
- **Routing**: See [routing.md](./routing.md)
- **Menus**: See [menu.md](./menu.md)
- **Main docs**: See `docs/` directory in plugin root
- **Issues**: Check GitHub issues or documentation

---

**Remember**: This is a quick reference. For detailed information, always refer to the specific guide files listed above.
