# Upgrade

## Unreleased (2.3.x)

### New options: disable the 404 listener and the SEO Twig extension

Both default to `true` (current behaviour):

```yaml
sylius_happy_cms:
    seo:
        # seo_metas(), seo_title(), seo_breadcrumb() and the happy_cms_seo global
        enable_twig_extension: true
    redirect_route:
        # redirect 404s to the matching redirect route, record unknown URLs as new redirect routes
        enable_not_found_listener: true
```

If you removed `sylius.happy_cms.exception_listener` or the `twig.extension` tag of
`Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension` in a compiler pass, you can use these options instead.
With `enable_twig_extension: false`, the `SeoExtension` service stays defined (for its constants and for
your own services) but its functions are no longer available in Twig: override the plugin templates
that call them (`default_layout.html.twig`, `front/document/default.html.twig`, `front/seo/*`,
`front/template_block/seo_metas.html.twig`) or provide functions with the same names.

### The CMF dynamic router is skipped while its route class does not exist

When the class of `cmf_routing.dynamic.persistence.orm.route_class` (`App\Entity\HappyCMS\Cmf\Route` by
default) does not exist, the dynamic router is no longer added to the chain router. Unknown URLs now
answer 404 instead of a 500 (`MappingException`). Nothing changes once the class exists.
