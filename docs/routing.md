<div align="center">

# Routing System

</div>

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [How It Works](#how-it-works)
- [Customization](#customization)
- [HTTP Caching](#http-caching)
- [Interfaces and Traits](#interfaces-and-traits)
- [Events](#events)
- [Commands](#commands)
- [Breadcrumb Navigation](#breadcrumb-navigation)
- [Cache Invalidation](#cache-invalidation)
- [Troubleshooting](#troubleshooting)
- [Additional Resources](#additional-resources)

## Overview

Sylius Happy CMS Plugin uses **Symfony CMF Routing** to provide database-driven dynamic routing. This means that URLs are stored in the database and managed automatically when you create or update CMS content.

## Key Features

- **Automatic route generation** - Routes are created automatically when you save routable entities
- **Multi-language support** - Each translation gets its own route
- **Hierarchical URLs** - Parent-child relationships are reflected in URLs
- **HTTP caching** - Built-in support for validation cache mode
- **Breadcrumb generation** - Automatic breadcrumb navigation from hierarchy

## How It Works

### Routable Entities

Any entity that implements `CmsRoutableInterface` and uses `EntityRouteTrait` becomes routable:

```php
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;

class BlogPost implements CmsRoutableInterface
{
    use EntityRouteTrait;

    // Your entity code...
}
```

### Automatic Route Management

When you save a routable entity (Page, BlogPost, etc.), the plugin automatically:

1. Creates or updates database routes for each language
2. Calculates URLs based on parent hierarchy
3. Updates child routes when parent URLs change
4. Stores routes in the `..._route` database table

### URL Structure

URLs follow this pattern:
```
/{{locale}}{{parent_path}}{{entity_slug}}
```

**Example hierarchy:**
```
Page "About" (slug: about)
└── Page "Team" (slug: team)
    └── Page "John Doe" (slug: john-doe)

Result URLs:
/en_US/about
/en_US/about/team
/en_US/about/team/john-doe
```

### Homepage Handling

Pages marked as homepage (`isHomePage() === true`) have special URL handling:
```
Homepage: /en_US/
Regular page: /en_US/services
```

### Default resources

By default, the plugin generates routes for the `Page` entity.
But you can create custom routable entities.

## Customization

### Create custom routable entities

(e.g., BlogPost, FAQ) with a single command:
```bash
php bin/console make:happy-cms:generate-cms-model
```



### Custom URL Structure

Override `getRouteStaticPrefix()` to customize URLs:

```php
public function getRouteStaticPrefix(TranslationInterface $translation): string
{
    // Example: Add date prefix for blog posts
    $date = $this->publishedAt->format('Y/m');
    return '/blog/' . $date . '/' . $translation->getSlug();
    // Result: /blog/2024/01/my-article
}
```

### Custom Controller

Forward routing to a custom controller:

```php
public function getRouteController(): ?string
{
    return 'App\Controller\BlogController::show';
}
```

### Custom Template

Use a custom template for rendering:

```php
public function getRouteTemplate(): ?string
{
    return 'blog/show.html.twig';
}
```

## HTTP Caching

The plugin supports HTTP validation cache mode:

```php
public function isHttpCacheEnabled(string $env, RouteInterface $route): bool
{
    return $env === 'prod';
}
```

**Framework configuration:**
```yaml
framework:
    http_cache:
        enabled: true
        default_ttl: 0
```

## Interfaces and Traits

### CmsRoutableInterface

**Location:** `src/Factory/CMS/CmsRoutableInterface.php`

The main interface that defines routable behavior. Key methods:
- `getRouteStaticPrefix()` - Calculate URL path
- `getRouteMethods()` - HTTP methods (GET, POST)
- `getRouteController()` - Custom controller
- `getRouteTemplate()` - Custom template
- `getBreadcrumbItems()` - Breadcrumb navigation
- `renderResponse()` - Response handling with cache

### EntityRouteTrait

**Location:** `src/Traits/EntityRouteTrait.php`

Provides default implementation of `CmsRoutableInterface`. Features:
- Route collection management
- Hierarchical URL calculation
- Multi-language support
- HTTP cache control
- Breadcrumb generation

## Events

### CalculateRouteStaticPrefixEvent

**Location:** `src/Event/Route/CalculateRouteStaticPrefixEvent.php`

Dispatched when calculating URL prefix. Use to inject custom prefixes:

```php
class BlogPostPrefixListener
{
    public function onCalculatePrefix(CalculateRouteStaticPrefixEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof BlogPost) {
            // Prefix all blog posts with /news
            $event->setRouteStaticPrefix('/news');
        }
    }
}
```

## Commands

```bash
php bin/console make:happy-cms:generate-cms-model
```
[Learn mode about this command usage here](./docs/commands/generate-cms-model.md)

## Breadcrumb Navigation

Automatically generated from entity hierarchy:

```twig
{{- seo_breadcrumb(resource.seo) -}}
```

## Cache Invalidation

Invalidate all route caches:

```bash
php bin/console happycms:cache:invalidate
```

## Troubleshooting

### Routes Not Generated

1. Check entity implements `CmsRoutableInterface`
2. Verify entity uses `EntityRouteTrait`
3. Clear cache: `php bin/console cache:clear`

### Wrong URLs

1. Check `getRouteStaticPrefix()` implementation
2. Verify parent hierarchy
3. Debug with `CalculateRouteStaticPrefixEvent`

### Database Routes

Check routes in database:
```sql
SELECT id, name, static_prefix FROM sylius_..._route;
```

## Additional Resources

For detailed technical documentation and AI-optimized guides:
- **[Routing System Guide for AI](./agents/routing.md)** - Comprehensive guide with code examples
- **[Symfony CMF Documentation](https://symfony.com/doc/current/cmf/index.html)** - Official CMF routing docs
