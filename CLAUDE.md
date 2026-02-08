# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Docker Environment (Recommended)
```bash
# Initialize Docker environment and install dependencies
make init

# Initialize database and run migrations
make database-init

# Load fixtures (optional)
make load-fixtures

# Start/stop containers
make up
make down

# Access containers
make php-shell
make node-shell
```

### Traditional Development
```bash
# Frontend setup
(cd vendor/sylius/test-application && yarn install)
(cd vendor/sylius/test-application && yarn build)
vendor/bin/console assets:install

# Database setup
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n

# Start server
symfony server:start -d
```

### Testing
```bash
# PHPUnit tests
vendor/bin/phpunit
make phpunit  # Docker

# Behat tests (non-JS)
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
make behat  # Docker

# Behat tests (JS scenarios)
# Requires Chrome headless and symfony server
APP_ENV=test symfony server:start --port=8080 --daemon
vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
```

### Code Quality
```bash
# PHPStan analysis
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
make phpstan  # Docker

# Coding standards
vendor/bin/ecs check
make ecs  # Docker
```

### Composer Scripts
```bash
# Database reset with fixtures
composer run database-reset

# Frontend rebuild
composer run frontend-clear

# Complete test app initialization
composer run test-app-init
```

## Architecture

This is the **SyliusHappyCMSPlugin** by Adeliom - a Sylius e-commerce plugin for CMS functionality. It provides a complete development environment with both traditional and Docker setups.

### Core Structure
- **Main Plugin Class**: `src/SyliusHappyCMSPlugin.php` - Entry point using `SyliusPluginTrait`
- **DI Extension**: `src/DependencyInjection/AdeliomSyliusHappyCMSExtension.php` - Handles service loading and Doctrine migrations
- **Services**: `config/services.xml` - Service definitions with XML configuration
- **Routes**: `config/routes/` - Separate admin and shop route definitions
- **Templates**: `templates/` - Twig templates for admin and shop with Twig hooks support

### Key Features
- **Test Application**: Uses `sylius/test-application` for plugin testing in isolation
- **Asset Management**: Webpack Encore for frontend asset compilation
- **Database**: Doctrine migrations with proper namespace handling
- **Testing**: Full Behat + PHPUnit setup with browser testing support
- **Code Quality**: PHPStan, ECS (Easy Coding Standard), and Rector integration

### Development Environment
- **Docker**: Complete containerized environment with PHP, Node.js, and database
- **Traditional**: Local Symfony server with manual dependency management
- **Frontend**: Yarn-based asset pipeline through test application

### Testing Strategy
- **Unit/Integration**: PHPUnit for isolated component testing
- **Functional**: Behat for feature testing with browser automation
- **Static Analysis**: PHPStan for type checking and code quality
- **Standards**: ECS for coding standard enforcement

### Database Configuration
Database credentials should be configured in:
- `tests/TestApplication/.env` (for development)
- `tests/TestApplication/.env.test` (for testing)

## AI Development Guides

This project includes specialized AI guides to assist with common plugin development tasks. These guides are optimized for AI agents to quickly understand and perform key operations.

### 🚀 Start Here

- **[Quick Start Guide](docs/agents/quickstart.md)** - Your first stop for common tasks
  - Task-based quick reference (create entity/block/override)
  - Standard workflow patterns
  - Troubleshooting quick fixes
  - Decision tree for choosing the right approach

### Quick Reference Guides

Located in `docs/agents/` directory:

- **[Commands Reference](docs/agents/commands.md)** - Complete command reference
  - All available CMS commands with detailed explanations
  - Execution order and workflows
  - Common patterns and troubleshooting

- **[Routable Entities](docs/agents/routable-entities.md)** - Creating custom CMS resources
  - Command: `php bin/console make:happy-cms:generate-cms-model`
  - Use cases: Blog, FAQ, Brand pages, custom entity pages
  - Includes automatic entity, repository, admin CRUD, routes generation

- **[Content Blocks](docs/agents/blocks.md)** - Creating flex and shared content blocks
  - Commands: `php bin/console make:happy-cms:block` (flex) or `make:happy-cms:block:shared` (shared)
  - Manual creation using `AbstractBlock` or `AbstractSharedBlockType`
  - Form building, template creation, block registration

- **[Override Blocks](docs/agents/override-blocks.md)** - Customizing default blocks
  - How to override: AccordionBlockType, CtaBlockType, GalleryBlockType, etc.
  - Service configuration and template customization
  - Step-by-step override process

- **[Routing System](docs/agents/routing.md)** - Understanding dynamic routing
  - How routes are automatically generated
  - URL hierarchy and multi-language support
  - Customizing routes, cache, and breadcrumbs
  - Troubleshooting routing issues

- **[Menu System](docs/agents/menu.md)** - Managing navigation menus
  - Creating hierarchical menus programmatically
  - Rendering menus in templates
  - Multi-language menu support
  - Performance optimization with ESI

### AI Agent Workflow

When working with this plugin, AI agents should:

1. **For new routable entities**: Use `make:happy-cms:generate-cms-model` command and follow `docs/agents/routable-entities.md`
2. **For new blocks**: Use `make:happy-cms:block` or `make:happy-cms:block:shared` and reference `docs/agents/blocks.md`
3. **For block customization**: Follow the override process in `docs/agents/override-blocks.md`
4. **After any generation**: Always run `php bin/console cache:clear` and database migrations if needed

### Available Commands Summary

```bash
# Plugin initialization
make:happy-cms:install              # Generate default files and configuration

# Entity generation
make:happy-cms:generate-cms-model   # Create routable entity with full CRUD

# Block generation
make:happy-cms:block                # Create flex block
make:happy-cms:block:shared         # Create shared block

# Demo content (optional)
make:happy-cms:demo-content         # Generate sample content for testing
```
