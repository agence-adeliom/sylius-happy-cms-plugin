# Preview

## Preview Modes

The HappyCMS plugin provides two ways to preview content before publishing:

### 1. Query Parameter Preview (New Page Builder)

The new page builder system uses a query parameter `?happy_cms_preview=1` to enable preview mode. This allows you to view draft content directly on the published URL.

**Example:**
```
https://yoursite.com/my-page?happy_cms_preview=1
```

**Features:**
- Shows unpublished blocks and draft content
- Uses custom preview template if configured (see `page_builder.preview_template` configuration)
- Access control managed via security voters
- Works with the new ContentBlock system

### 2. Preview Route (Version < 2.1, without new page builder)

The legacy system uses a separate preview route with `-preview` suffix.

**Example:**
```
https://yoursite.com/my-page-preview
```

## Signed preview of the page builder

The page builder shows the page in an iframe that points to the front. The admin and the front may use different
hosts: the preview does not rely on the admin session.

1. The page builder template renders the iframe URL with `happy_cms_signed_preview_url(entity, resource, locale)`.
   The URL is built from the route of the content (so from the hostname of its channel), then signed with the Symfony
   `UriSigner` (kernel secret) and an expiration.
2. On the front, `SignedPreviewVoter` grants `page_builder` to a request whose signature is intact, not expired, and
   made for this content, this resource and this locale. A URL signed for a page gives no access to another page or
   another locale.
3. `PreviewResponseListener` lets the admin origin frame the response and injects `page-builder-preview.js`, which
   talks to the page builder with `postMessage`. Both sides check the origin and the source window of every message.
4. Before each reload of the preview (block saved, moved, published...), the page builder asks a new signed URL to
   `GET /happy-cms-builder/{resource}/{id}/preview-url?locale=xx`, protected by the admin firewall.

Signed parameters: `happy_cms_preview=1`, `_hcms_resource`, `_hcms_id`, `_hcms_locale`, `_hcms_admin_host` (origin of
the admin, `scheme://host[:port]`), `_hcms_admin_locale` (optional, language of the admin), `_expiration` and `_hash`.

### Empty blocks and minimal height

Each block is wrapped in `.content-block-wrapper`. The preview agent sets its minimal height from the edit button, so
the button is never cut, and flags the blocks that render nothing visible.

- `--hcms-block-min-height` is the minimal height of a block: the height of the edit button plus
  `--hcms-block-gap` above and below (76px by default).
- `--hcms-block-state-min-height` applies to unpublished and deleted blocks: state badge, button and gaps (about 130px).
- A block whose content is under 4px high gets the class `is-empty`. Its `.content-block-empty-placeholder` shows the
  icon and the name of the block type, with the notice "No visible output in the preview". The class is removed when
  the content appears later (script, image).
- A block hidden with `visibility: hidden` or `opacity: 0` keeps its height: it is not flagged.

Override the variables in a stylesheet loaded by the front, with a selector more specific than the one of the agent:

```css
body .content-block-wrapper {
    --hcms-block-gap: 24px;
}
```

The preview texts (edit button, "Unpublished block", "Deleted block", notice of the placeholder) and the name of the
block type are translated in the language of the admin carried by the signed URL. The keys are
`sylius_happy_cms.page_builder.edit_block` and `sylius_happy_cms.page_builder.preview_overlay.*`.

### Configuration

```yaml
sylius_happy_cms:
    page_builder:
        preview_url_ttl: 300 # lifetime of the signed URL, in seconds
```

The signature only has to cover the loading of the preview: keep the value short.

When the admin is served from its own host:

- give a `hostname` to every channel whose pages are edited in the page builder;
- behind a proxy that terminates TLS, configure `framework.trusted_proxies` (and `trusted_headers`), as Symfony already
  requires to generate correct URLs.

### Troubleshooting the signed preview

**"The preview did not answer" after 10 seconds.** The preview script did not announce itself. Check, in this order:

- the channel of the page has a `hostname` (without it, the iframe targets the admin host);
- the iframe URL opens in a private window (status 200). A 403 means a refused signature: see the proxy point below;
- the page template outputs a `</body>` tag, where the script is injected.

**The preview stays blank and the console reports `frame-ancestors`.** The response already sends a
`Content-Security-Policy` with a `frame-ancestors` directive: the plugin leaves it untouched. Add the admin origin to it
for the preview responses.

**"Your admin session has expired".** The page builder could not get a new signed URL: the admin session expired or the
user lost the `page_builder` right. Reload the page builder and log in again.

**Every signed preview is refused (403) behind a proxy.** The front sees `http` or an internal host while the URL was
signed with `https` and the public host. Configure `trusted_proxies`.

**A front script fails in the preview with a cross-origin error.** Reading `window.top` or `window.parent` is forbidden
across origins. Read `window.location` instead.

## Troubleshooting: "Access Denied to preview content document"

This section is about the session-based preview links (`happy_cms_path(page, true)`). The page builder uses a signed
URL (see above) and does not need the admin session on the shop. The refusal is a 403 response. For a session-based
link, the admin session must be available on the shop side. Check that:

- the `admin_happy_cms_content_builder` firewall is declared in `security.yaml` **before** the `shop` firewall (see the README, step 5);
- `role_hierarchy` contains `ROLE_ADMINISTRATION_ACCESS: [ ROLE_HAPPY_CMS_CONTENT_BUILDER ]`;
- you are logged in to the admin with a user having that role, in the same browser as the page builder.

In this repository's test application, run `make configure-preview-mode` (or `composer run configure-preview-mode`) then clear the cache.

## Access Control

The roles allowed to access a resource preview can be defined using the `ContentPreview` attribute.

The example below only gives access to the preview of a page to the EDITOR role.

```php
<?php

namespace App\Entity\HappyCMS\Page;

use Adeliom\SyliusHappyCMSPlugin\Attribute as CMS;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page as BasePage;

// .... #[ORM\Entity ...
#[CMS\ContentPreview(['EDITOR'])]
class Page extends BasePage
{
```

## Using Preview in Twig

You can generate preview URLs using the provided Twig functions:

```twig
{# Generate preview URL with query parameter #}
{{ happy_cms_path(page, true) }}
{# Result: /my-page?happy_cms_preview=1 #}

{# Generate preview URL by ID #}
{{ happy_cms_path_by_id(page.id, 'sylius_happy_cms.page', 'en_US', null, true) }}

{# Generate preview URL by key #}
{{ happy_cms_path_by_key('homepage', 'sylius_happy_cms.page', true) }}
```

## Custom Preview Template

You can configure a custom template for preview mode to show content without header/footer or other layout elements. See the main configuration documentation for details on the `page_builder.preview_template` option.


