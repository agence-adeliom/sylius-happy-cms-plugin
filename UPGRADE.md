# Upgrade

## Unreleased (2.3.x)

### CMS routes: URL pattern, trailing slash, parent path resolvers

New options, defaults keep the current routes:

```yaml
sylius_happy_cms:
    routing:
        url_pattern: '/{{locale}}{{other_entity_path}}{{current_entity_path}}'
        trailing_slash: false
```

Changing them does not rewrite the stored routes: re-save the routable entities (or re-index them) afterwards.

With `trailing_slash: true`, the path without the final slash is redirected (301) to the CMS route only when its
content is online (`CmsRoutableInterface::isOnline()`); otherwise it answers 404, like the route itself.

New extension point: `Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteParentPathResolverInterface` (autoconfigured),
fills `{{other_entity_path}}` before `CalculateRouteStaticPrefixEvent` is dispatched.

`EntityRouteIndexer`:

- `manageRoutes()`, `computeRoutes()` and `rewriteOtherStaticPrefix()` are now `protected` (were `private`);
- the constructor takes three new optional arguments (`$urlPattern`, `$trailingSlash`, `$parentPathResolvers`);
  a service definition overriding the indexer with the four original arguments keeps the current behaviour;
- repeated slashes in a computed path are collapsed (`/en_US//about` → `/en_US/about`).
