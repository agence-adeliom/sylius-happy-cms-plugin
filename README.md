<div align="center">

# Sylius Happy CMS Plugin


</div>


![Happy CMS banner](docs/screens/happy_cms.jpg "Happy CMS banner")

<div align="center">

[Overview](#overview) • [Installation](#installation) • [Documentation](#documentation)

</div>

---

## Overview

Happy CMS is a simple Content Management System (CMS) plugin for Sylius that enables you to create and manage dynamic pages based on Sylius custom and routable resources. 

The plugin brings awesome CMS features to Sylius, including:
- **Page Builder**: A back-office visual interface for preview and managing pages content with various content blocks.
- **Media Management**: Organize and manage media files (images, videos, documents) used in your CMS pages. Built with Flysystem storage abstraction layer.
- **SEO**: Built-in SEO management for optimizing your pages for search engines.
- **Multilingue**: Full support for multiple languages and locales.
- **Custom Routable Resource**: Define your own entities (blog, faq, etc...) that can be routed and displayed as CMS pages with specific logic, routes and templates.
- **Menu Management**: Create and manage menus for your site navigation.
- **Flexible Blocks**: Use default (or create custom) various types of content blocks (text, images, videos, etc.) within your pages.
- **Shared Blocks**: Reusable content blocks that can be used across multiple pages.
- **Helpers**:
    - Commands to generate entities, repositories and admin classes for your custom routable resources.
    - Commands to generate blocks easily.
- **AI**: Leverage AI to assist in generating content for your pages (requires API key).
- **CRUD**: Integrated with [Sylius Easy CRUD Plugin](https://github.com/agence-adeliom/sylius-easy-crud-plugin) for simplified Sylius resources CRUD management.
---

## Installation and documentation

1.  [Install this plugin](#installation)
2.  [Explore documentation](#documentation)

## Versions

| Plugin Version | Sylius         | Php           | Symfony  | New - Guide                                                                                                                                                              | Support |
|----------------|----------------|---------------|----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| 1.13, 1.14     | 1.13, 1.14     | 8.2, 8.3, 8.4 | 6.4, 7.x | [See installation guide](https://github.com/agence-adeliom/sylius-happy-cms-plugin/tree/v1.14.12?tab=readme-ov-file#installation)                                        | No      |
| ^2.0.0         | ^2.0.0         | 8.2, 8.3, 8.4 | 6.4, 7.x | - Sylius 2.0 [BC] [Migrate from v1 guide](./docs/migration/SYLIUS_2.md)                                                                                                  | No      |
| ^2.1.0         | ^2.1.0, ^2.2.0 | 8.3, 8.4, 8.5 | 6.4, 7.x | - [See installation guide](#installation)<br/>- New content model persistence, new page builder [BC]  - [Migrate from ^2.0.0 version](./docs/migration/CONTENT_BLOCK.md) | Yes     |
| ^2.3.0         | ^2.3.0         | 8.4, 8.5      | 7.4, 8.x | - [See installation guide](#installation)<br/>- Sylius 2.3 / Symfony 8 support                                                                                                   | Yes     |

## Feature preview

![Page Builder preview](docs/screens/content-manager.png "Page Builder preview")

![Page Builder preview](docs/screens/media-manager.png "Media manager preview")

## Installation

### 1. Install via Composer

```bash
composer require agence-adeliom/sylius-happy-cms-plugin --no-scripts
composer require --dev symfony/maker-bundle --no-scripts
```

### 2. Enable the Bundle

Add the plugin to `config/bundles.php`:

```php
<?php

return [
    // ...
    Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
    Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
   
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true, 'test' => true],
];
```

### 3. Import Configuration

In `config/packages/_sylius.yaml`:

```yaml
imports:
  - { resource: "@SyliusEasyCrudPlugin/config/config.yaml" }
  - { resource: "@SyliusHappyCMSPlugin/config/config.yaml" }
```

### 4. Import Routes

In `config/routes.yaml`:

```yaml
sylius_happy_cms:
  resource: "@SyliusHappyCMSPlugin/config/routes.yaml"
  
sylius_easy_crud:
  resource: "@SyliusEasyCrudPlugin/config/routes.yaml"
```

### 5. Configure your firewall to protect preview routes

All admin users with ROLE_ALLOWED_TO_SWITCH AND ROLE_HAPPY_CMS_CONTENT_BUILDER will be able to preview CMS routable entities.

In config/packages/security.yaml

```yaml 
security:
    firewalls:
        #... 
        admin_happy_cms_content_builder:
            switch_user: { role: ROLE_ALLOWED_TO_SWITCH }
            context: admin
            pattern: "%sylius.security.shop_regex%"
            request_matcher: Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher
            provider: sylius_admin_user_provider
    #... 
    role_hierarchy:
        ROLE_ADMINISTRATION_ACCESS: [ ROLE_HAPPY_CMS_CONTENT_BUILDER ]
```

### 5.1 Allow the page builder iframes (X-Frame-Options)

The page builder displays admin **and front** pages (preview) inside iframes, so every response must allow same-origin framing.
Send a single `X-Frame-Options: SAMEORIGIN` header, from one source only:

- If you use [NelmioSecurityBundle](https://github.com/nelmio/NelmioSecurityBundle), use `SAMEORIGIN`, not `DENY`, on the whole site:

  ```yaml
  # config/packages/nelmio_security.yaml
  nelmio_security:
      clickjacking:
          paths:
              '^/.*': SAMEORIGIN
  ```

  and remove the Sylius subscriber, which also sets this header on every response (`src/Kernel.php`):

  ```php
  final class Kernel extends BaseKernel implements CompilerPassInterface
  {
      use MicroKernelTrait;

      public function process(ContainerBuilder $container): void
      {
          $container->removeDefinition('sylius.event_subscriber.x_frame_options');
      }
  }
  ```

- Remove `Header set X-Frame-Options SAMEORIGIN` from `public/.htaccess` (Sylius recipe, Apache) and do not set the header in the web server (Caddyfile, nginx) or the ingress either.
- Serve the admin and the shop on the same host, otherwise `SAMEORIGIN` blocks the preview (use CSP `frame-ancestors` in that case).

Two conflicting headers (`DENY, SAMEORIGIN`) make the browser fall back to `DENY`, and the page builder breaks with:

```
Refused to display '…' in a frame because it set multiple 'X-Frame-Options' headers with conflicting values ('DENY, SAMEORIGIN'). Falling back to 'deny'.
page-builder.js: Uncaught SecurityError: Failed to read a named property 'href' from 'Location': Blocked a frame with origin "…" from accessing a cross-origin frame.
```

Check with `curl -skI https://your-shop.local/en_US/ | grep -i x-frame-options`: exactly one `SAMEORIGIN` line is expected.

### 5. Generate default files in your project (entities, repositories and admin classes) :

Actually, we don't have Symfony recipes, so we created a command to generate files automatically.

```bash
php bin/console make:happy-cms:install
```
This command will :
- Create all entities, repositories and admin class
- update config/routes.yaml by adding route properly declared
- update config/packages/sylius_resource.yaml by adding sylius routes properly declared
- update config/packages/sylius_happy_cms.yaml by adding new files properly declared

If something goes wrong, you can do those actions manually, check [detailed configuration](./docs/DETAILED_CONFIG.md).

### 6. Install Assets

```bash
php bin/console assets:install
```

### 7. Update database

```bash
php bin/console doc:mig:diff
php bin/console doc:mig:mig
php bin/console cache:clear
# To compile our symfony UX components, you need to re run npm install and npm run build in the root of your project
npm install
npm run build
```

### 8. (Optional) Configure AI Development Guides

If you're using AI assistants (like Claude Code, GitHub Copilot, or Cursor), configure them to use the plugin's specialized guides:

#### For Claude Code users:

Create or update `CLAUDE.md` in your project root:

```markdown
# Project Instructions

[Your existing project instructions...]

## Sylius Happy CMS Plugin

This project uses [Sylius Happy CMS Plugin](https://github.com/agence-adeliom/sylius-happy-cms-plugin) for content management.

### AI Development Guides

Import the AI development guides for efficient development:

- **Happy CMS development guides**: `vendor/agence-adeliom/sylius-happy-cms-plugin/docs/agents/CLAUDE.md`
```

#### For other AI assistants:

Create or update `.cursorrules`, `AGENTS.md`, or your AI configuration file with similar content pointing to the guides in `vendor/agence-adeliom/sylius-happy-cms-plugin/docs/agents/`.

Add "See @CLAUDE.md"

---

## Documentation

- **[Override default Sylius homepage](./docs/homepage.md)**
- **[Routing](./docs/routing.md)**
- **[Seo](./docs/seo.md)**
- **[Medias](./docs/medias.md)**
- **[Blocks](./docs/blocks.md)**
- **[Menu](./docs/menu.md)**
- **[AI](./docs/ai.md)**
- **[Full plugin configuration](./docs/configuration.md)**
- **[Contribution](./docs/contribution.md)**

Start by read the documentation, then you can : 
- Use bundles commands to create new routable resources (blog, faq, brand pages, etc.) [see here](./docs/commands/generate-cms-model.md)
- Use bundle commands to create new content blocks (flex or shared)

---

<div align="center">

**If this plugin helped you, please consider giving it a ⭐ on GitHub!**

Made with ❤️ by [Adeliom](https://www.adeliom.com/)


[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
[![Sylius Version](https://img.shields.io/badge/sylius-%5E2.0-blue)](https://sylius.com)
[![Latest Version](https://img.shields.io/packagist/v/agence-adeliom/sylius-easy-crud-plugin)](https://packagist.org/packages/agence-adeliom/sylius-happy-cms-plugin)

</div>
