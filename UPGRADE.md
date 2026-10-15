# Upgrade

## Unreleased (2.3.x)

### New options: control the 404 redirections and the SEO Twig extension

Both default to `true` (current behaviour):

```yaml
sylius_happy_cms:
    seo:
        # seo_metas(), seo_title(), seo_breadcrumb() and the happy_cms_seo global
        enable_twig_extension: true
    redirect_route:
        # on a 404, redirect to the matching redirect route when it has a target
        enable_redirection: true
        # on a 404, record the unknown URL as a new redirect route (one row per URL and host)
        enable_not_found_recording: true
```

The two features of the 404 listener can be turned off independently:

| `enable_redirection` | `enable_not_found_recording` | Result |
|---|---|---|
| `true` | `true` | Current behaviour: redirections are served, unknown URLs are recorded. |
| `true` | `false` | Redirections configured in the admin are served, the `redirect_route` table no longer grows with crawler and typo URLs. |
| `false` | `true` | Unknown URLs are recorded, but nothing is redirected (collect the URLs first, enable the redirections later). |
| `false` | `false` | The listener `sylius.happy_cms.exception_listener` is not registered. |

If you removed `sylius.happy_cms.exception_listener` (both options to `false`) or the `twig.extension` tag of
`Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension` in a compiler pass, you can use these options instead.
With `enable_twig_extension: false`, the `SeoExtension` service stays defined (for its constants and for
your own services) but its functions are no longer available in Twig: override the plugin templates
that call them (`default_layout.html.twig`, `front/document/default.html.twig`, `front/seo/*`,
`front/template_block/seo_metas.html.twig`) or provide functions with the same names.

### The CMF dynamic router is skipped while its route class does not exist

When the class of `cmf_routing.dynamic.persistence.orm.route_class` (`App\Entity\HappyCMS\Cmf\Route` by
default) does not exist, the dynamic router is no longer added to the chain router. Unknown URLs now
answer 404 instead of a 500 (`MappingException`). Nothing changes once the class exists.
