# Upgrade

## Unreleased (2.3.x)

### SEO Twig functions typed on `SeoInterface`

`seo_metas()` and `seo_title()` now accept any `Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface` (they used to
require the `Seo` class, a `TypeError` for any other implementation); `seo_title()` also accepts `null`.

If you extend `Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension` and override `renderSeoMetas()` or
`renderSeoTitle()`, widen the parameter types of your methods:

```diff
-public function renderSeoMetas(?Seo $seo): ?Markup
+public function renderSeoMetas(?SeoInterface $seo): ?Markup

-public function renderSeoTitle(string|Seo $seo): string
+public function renderSeoTitle(string|SeoInterface|null $seo): string
```

### New option: SEO templates

```yaml
sylius_happy_cms:
    seo:
        templates:
            metas: '@SyliusHappyCMSPlugin/front/seo/block-metas.html.twig'
            breadcrumb: '@SyliusHappyCMSPlugin/front/seo/block-breadcrumb.html.twig'
```

The defaults are the current templates. `SeoExtension` takes them as a new, optional, last constructor argument.

### Breadcrumb markup

`front/seo/block-breadcrumb.html.twig` now uses `https://schema.org/BreadcrumbList` and `https://schema.org/ListItem`,
keeps an absolute first link as is (it used to become `https:/host/`) and renders nothing when the breadcrumb is
empty. If you override this template, apply the same changes.
