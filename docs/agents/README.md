# AI Development Guides

This directory contains specialized guides designed to help AI agents efficiently work with the Sylius Happy CMS Plugin.

## Purpose

These guides provide step-by-step instructions, code examples, and best practices specifically formatted for AI assistants (Claude Code, GitHub Copilot, Cursor, etc.) to:
- Quickly understand plugin capabilities
- Execute common development tasks
- Follow established patterns and conventions
- Troubleshoot common issues

## Available Guides

### [quickstart.md](./quickstart.md) ⭐ START HERE
**Quick Start Guide for AI Agents**

Your first stop when working with the plugin. Provides:
- Task-based quick reference for common operations
- Standard workflow patterns
- Quick troubleshooting tips
- Decision tree for choosing the right tool
- Learning path for new AI agents

### [commands.md](./commands.md)
**Complete Commands Reference**

Comprehensive reference of all Sylius Happy CMS Plugin commands. Covers:
- Installation commands
- Entity generation commands
- Block generation commands
- Demo content commands
- Standard Symfony commands related to CMS
- Command execution order and workflows
- Troubleshooting commands

### [routable-entities.md](./routable-entities.md)
**Creating Custom CMS Resources**

Learn how to create routable entities (Blog, FAQ, Brand pages, etc.) using the `make:happy-cms:generate-cms-model` command. Covers:
- Interactive entity generation
- Automatic configuration setup
- Database migrations
- Common use cases and examples

### [blocks.md](./blocks.md)
**Creating Content Blocks**

Complete guide to creating flex and shared blocks using maker commands or manual implementation. Includes:
- Flex vs. Shared blocks explanation
- Form building with Symfony form types
- Template creation and rendering
- Block registration and configuration
- Best practices and field types reference

### [override-blocks.md](./override-blocks.md)
**Customizing Default Blocks**

Step-by-step guide to overriding and customizing the plugin's default blocks. Covers:
- List of available default blocks
- Override process (copy, modify, register)
- Service ID mapping
- Common customization scenarios
- Template override strategies

### [routing.md](./routing.md)
**Understanding the Routing System**

Complete explanation of how dynamic routing works in the plugin. Covers:
- Automatic route generation with Symfony CMF
- URL hierarchy and multi-language support
- Customizing routes, controllers, and templates
- HTTP cache management
- Breadcrumb navigation
- Events and troubleshooting

### [menu.md](./menu.md)
**Menu System Management**

Complete guide to creating and managing navigation menus. Covers:
- Data model (Menu, MenuItem, MenuItemTranslation)
- Creating menus programmatically
- Hierarchical menu structures
- Rendering menus in templates
- Multi-language support
- Performance optimization with ESI

## How to Use These Guides

### For AI Assistants

These guides are referenced in the project's `CLAUDE.md` file and are automatically loaded when AI agents work on the project. They provide context-specific instructions for common tasks.

### For Developers

While optimized for AI consumption, these guides are also useful for developers:
1. Read the guide relevant to your task
2. Follow the step-by-step instructions
3. Reference code examples
4. Use troubleshooting sections when needed

## Integration

### Claude Code
These guides are automatically available when working with Claude Code if properly configured in `CLAUDE.md`.

### Other AI Tools
Reference these files in your AI configuration:
- **Cursor**: Add to `.cursorrules`
- **GitHub Copilot**: Reference in project documentation
- **Other assistants**: Import into `AGENTS.md` or equivalent

## Maintenance

When updating these guides:
1. Keep code examples current with plugin version
2. Test all commands and examples
3. Update troubleshooting sections based on common issues
4. Maintain consistent formatting for AI parsing
5. Include version-specific notes when relevant

## Contributing

If you find issues or want to improve these guides:
1. Check the main [CONTRIBUTION.md](../docs/CONTRIBUTION.md)
2. Submit issues or PRs to the repository
3. Follow existing formatting conventions
4. Test examples before submitting

## Related Documentation

For human-readable documentation, see:
- [Main Documentation](../docs/)
- [README.md](../README.md)
- [About blocks](../docs/blocks.md)
- [About routing](../docs/routing.md)
- [About media](../docs/medias.md)
