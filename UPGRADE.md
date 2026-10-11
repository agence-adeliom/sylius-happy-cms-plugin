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
content is online (`CmsRoutableInterface::isOnline()`, content loaded from the `_content_id` of the route when the
dynamic router has no content enhancer); otherwise it answers 404, like the route itself.

New extension point: `Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteParentPathResolverInterface` (autoconfigured),
fills `{{other_entity_path}}` before `CalculateRouteStaticPrefixEvent` is dispatched.

`EntityRouteIndexer`:

- `manageRoutes()`, `computeRoutes()` and `rewriteOtherStaticPrefix()` are now `protected` (were `private`);
- the constructor takes three new optional arguments (`$urlPattern`, `$trailingSlash`, `$parentPathResolvers`);
  a service definition overriding the indexer with the four original arguments keeps the current behaviour;
- repeated slashes in a computed path are collapsed (`/en_US//about` → `/en_US/about`).

### CMS routes computed within the flush, `happycms:routes:index`

`EntityRouteIndexer` now listens to `onFlush` and `postPersist` (it used to listen to `postPersist` and `postUpdate`
and flush the entity manager from them). If you redefined the `sylius.happy_cms.plugin.listener.entity_route_indexer`
service, update its tags:

```yaml
tags:
    - { name: 'doctrine.event_listener', event: 'onFlush', priority: 500, connection: 'default' }
    - { name: 'doctrine.event_listener', event: 'postPersist', priority: 500, connection: 'default' }
```

`postUpdate()` is kept as a no-op. New public methods: `onFlush()`, `index()` and `rewriteStaticPrefix()`.

Renaming a path now moves only the routes below it at a `/` boundary (`/door` no longer rewrites `/doors`), and
only the leading part of their path is replaced.

New option `sylius_happy_cms.routing.rewrite_child_routes` (default `true`, current behaviour): `false` disables
the automatic move of the routes below a renamed path, for projects where two contents of different types can have
the same slug (their children share the prefix). The indexer takes it as an eighth optional constructor argument.

New command `happycms:routes:index` (`--class`, `--batch-size`, `--dry-run`, `--save-entities`) to index existing
entities. It writes the route tables only and does not save the entities (no `updated_at` change, no update
listener); `--save-entities` saves them as well.

