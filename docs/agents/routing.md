# Routing System - AI Guide

This guide explains how the dynamic routing system works in Sylius Happy CMS Plugin. The plugin uses Symfony CMF Routing for database-driven dynamic routes.

## Overview

The routing system automatically generates and manages routes for CMS entities. When you create or update a routable entity (Page, Blog post, etc.), the system automatically:
- Creates database routes for each language
- Handles URL hierarchy (parent/child relationships)
- Manages route updates when slugs change
- Provides breadcrumb navigation
- Supports HTTP caching

## Key Components

### 1. CmsRoutableInterface

**Location**: `src/Factory/CMS/CmsRoutableInterface.php`

The interface that makes an entity routable. Defines methods for:
- Route generation and management
- URL structure (prefix, pattern, host)
- HTTP configuration (methods, schemes, cache)
- Rendering and response handling
- Breadcrumb generation

### 2. EntityRouteTrait

**Location**: `src/Traits/EntityRouteTrait.php`

Provides default implementation of `CmsRoutableInterface`. Key features:
- Manages route collection for the entity
- Calculates URL from parent hierarchy
- Handles multi-language routes
- Provides cache control

**Usage in entities**:
```php
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;

class BlogPost implements CmsRoutableInterface
{
    use EntityRouteTrait;

    // Your entity properties and methods...
}
```

### 3. Route Entity

**Location**: `src/Entity/Cmf/Route.php`

Database entity that stores route information:
- `name` - Unique route identifier
- `staticPrefix` - The URL path (e.g., `/en/blog/my-article`)
- `methods` - HTTP methods (GET, POST)
- `defaults` - Route defaults (locale, controller)
- `requirements` - Route requirements (locale constraint)
- `schemes` - Allowed schemes (http, https)
- `host` - Domain hostname
- `lastModification` - Last update timestamp (for cache)

### 4. EntityRouteIndexer

**Location**: `src/EventListener/EntityRouteIndexer.php`

Doctrine event listener that automatically manages routes:
- Listens to `postPersist` and `postUpdate` events
- Creates/updates routes for each translation
- Handles route hierarchy when slugs change
- Cascades URL updates to child entities

## How Routing Works

### Automatic Route Generation

When you save a routable entity:

1. **EntityRouteIndexer** intercepts the Doctrine event
2. For each translation, it creates or updates a route:
   - Route name: `route_online_{locale}_{entity_class}_{id}`
   - URL path: Calculated from parent hierarchy + entity slug
3. Routes are persisted to database
4. If URL changes, child routes are updated automatically

### URL Structure

URLs are built hierarchically:

```
/{{locale}}{{other_entity_path}}{{current_entity_path}}{{variable_pattern}}
```

**Example hierarchy**:
```
Page "About" (slug: about)
└── Page "Team" (slug: team)
    └── Page "John Doe" (slug: john-doe)

Result URLs:
/en/about
/en/about/team
/en/about/team/john-doe
```

**For homepage**: If `isHomePage()` returns true, the slug is omitted:
```
Homepage (slug: home, isHomePage: true) → /en/
Regular page (slug: services) → /en/services
```

### Multi-Language Support

Each translation gets its own route:
```php
// English route
route_online_en_App_Entity_BlogPost_123
Static prefix: /en/blog/my-article

// French route
route_online_fr_App_Entity_BlogPost_123
Static prefix: /fr/blog/mon-article
```

## Customizing Routes

### Override URL Structure

Override `getRouteStaticPrefix()` in your entity:

```php
public function getRouteStaticPrefix(TranslationInterface $translation): string
{
    // Custom URL structure
    return '/custom-prefix/' . $translation->getSlug();
}
```

### Add Variable Pattern

For dynamic URL parameters, override `getVariablePattern()`:

```php
public function getVariablePattern(TranslationInterface $translation): string
{
    // Add dynamic segment: /blog/my-article/{page}
    return '/{page}';
}
```

### Custom Controller

Forward to a custom controller instead of default:

```php
public function getRouteController(): ?string
{
    // Use custom controller action
    return 'App\Controller\BlogController::show';
}
```

### Custom Template

Use a custom template for rendering:

```php
public function getRouteTemplate(): ?string
{
    // Use custom template
    return 'blog/show.html.twig';
}
```

### Change Route Methods

Allow different HTTP methods:

```php
public function getRouteMethods(): array
{
    // Allow GET and POST
    return ['GET', 'POST'];
}
```

### Custom Hostname

Set specific hostname for route:

```php
public function getRouteHost(TranslationInterface $translation): ?string
{
    // Use specific domain
    return 'blog.example.com';
}
```

## HTTP Cache Management

The trait provides HTTP cache support with validation mode.

### Default Behavior

```php
public function isHttpCacheEnabled(string $env, RouteInterface $route): bool
{
    // Disabled by default
    return false;

    // Enable in production only
    // return $env === 'prod';
}
```

### Cache Configuration

When cache is enabled, the `renderResponse()` method:
- Sets `Last-Modified` header based on route modification time
- Enables validation cache mode (no TTL, must revalidate)
- Forces public cache

**Framework configuration needed**:
```yaml
framework:
    http_cache:
        enabled: true
        default_ttl: 0
```

### Cache Invalidation

```bash
# Invalidate all route caches
php bin/console happycms:cache:invalidate
```

## Breadcrumb Navigation

The trait automatically generates breadcrumbs from entity hierarchy:

```php
public function getBreadcrumbItems(): array
{
    // Returns array of:
    // [
    //     ['label' => 'About', 'route' => RouteInterface],
    //     ['label' => 'Team', 'route' => RouteInterface],
    //     ['label' => 'John Doe', 'route' => RouteInterface]
    // ]
}
```

**In Twig templates**:
```twig
{% for item in entity.breadcrumbItems %}
    <a href="{{ path(item.route) }}">{{ item.label }}</a>
{% endfor %}
```

## Events

### CalculateRouteStaticPrefixEvent

Dispatched when calculating URL prefix. Use to inject custom prefixes.

**Location**: `src/Event/Route/CalculateRouteStaticPrefixEvent.php`

**Example use case**: Prefix blog posts with a page slug
```php
// Blog posts under page "News"
// Result: /en/news/my-blog-post
// Instead of: /en/my-blog-post

class BlogPostPrefixListener
{
    public function onCalculatePrefix(CalculateRouteStaticPrefixEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof BlogPost) {
            // Add "news" page prefix
            $event->setRouteStaticPrefix('/news');
        }
    }
}
```

## Important Notes

### Route Naming Convention

Routes are named: `route_{type}_{locale}_{entity_class}_{id}`
- `{type}`: `online` or `preview`
- `{locale}`: Language code (en, fr, etc.)
- `{entity_class}`: Entity class name with underscores
- `{id}`: Entity ID

Example: `route_online_en_App_Entity_BlogPost_42`

### Route Updates

When changing entity slugs:
1. Entity's own routes are updated
2. All child entity routes are recalculated
3. URLs maintain hierarchy automatically

### Parent Hierarchy

The trait uses `getParent()` method to build URL hierarchy:
```php
public function getParent(): ?PageInterface
{
    // Return parent entity for hierarchical URLs
    return $this->parent;
}
```

### Homepage Detection

The trait checks for `isHomePage()` method:
- If true: No slug in URL (just `/en/`)
- If false: Includes slug (`/en/about`)

## Database Schema

Routes are stored in database table (usually `cmf_route`):

```
id                  - Route ID
name                - Unique route name
static_prefix       - URL path
variable_pattern    - Dynamic segments
defaults            - Route defaults (JSON)
requirements        - Route requirements (JSON)
options             - Route options (JSON)
schemes             - Allowed schemes (JSON)
methods             - HTTP methods (JSON)
host                - Hostname
position            - Sort order
last_modification   - Update timestamp
```

## Troubleshooting

### Routes Not Generated

1. Check entity implements `CmsRoutableInterface`
2. Verify entity uses `EntityRouteTrait` or implements all methods
3. Clear cache: `php bin/console cache:clear`
4. Check database for routes

### Wrong URLs

1. Check `getRouteStaticPrefix()` implementation
2. Verify parent hierarchy with `getParent()`
3. Check for custom event listeners modifying prefix
4. Debug with `CalculateRouteStaticPrefixEvent`

### Child Routes Not Updating

1. Verify `EntityRouteIndexer` is registered
2. Check Doctrine events are working
3. Clear cache and regenerate routes

### Cache Not Working

1. Enable framework HTTP cache
2. Check `isHttpCacheEnabled()` returns true
3. Verify `Last-Modified` header in response
4. Test with `curl -I` to see headers

## Best Practices

1. **Use the trait**: Don't reimplement routing from scratch
2. **Override selectively**: Only override methods you need to customize
3. **Test hierarchy**: Verify parent/child URL relationships
4. **Cache carefully**: Consider cache implications for dynamic content
5. **Event listeners**: Use events for cross-cutting routing concerns
6. **Clear cache**: Always clear cache after routing changes
7. **Database cleanup**: Periodically clean orphaned routes

## Example: Custom Blog Routing

```php
namespace App\Entity;

use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Sylius\Resource\Model\TranslationInterface;

class BlogPost implements CmsRoutableInterface
{
    use EntityRouteTrait;

    // Override to add date in URL: /blog/2024/01/my-post
    public function getRouteStaticPrefix(TranslationInterface $translation): string
    {
        $date = $this->publishedAt->format('Y/m');
        return '/blog/' . $date . '/' . $translation->getSlug();
    }

    // Use custom controller
    public function getRouteController(): ?string
    {
        return 'App\Controller\BlogController::show';
    }

    // Enable cache in production
    public function isHttpCacheEnabled(string $env, RouteInterface $route): bool
    {
        return $env === 'prod';
    }
}
```
