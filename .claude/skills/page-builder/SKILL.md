---
name: page-builder
description: Use when working on the HappyCMS page builder in this plugin - preview iframe, signed preview URL (happy_cms_signed_preview_url, PreviewUrlSigner, PreviewUrlGenerator, SignedPreviewRequestValidator, SignedPreviewVoter), PreviewResponseListener (frame-ancestors, X-Frame-Options, injected page-builder-preview.js), postMessage protocol (preview-protocol.js, preview-agent.js, preview-bridge.js), PageBuilderController (builderAction, previewUrlAction), BlockEditor.js reloads, preview_url_ttl, admin on its own host (ADMIN_HOST), happy_cms_preview=1, ContentDocumentVoter::PAGE_BUILDER.
---

# Page builder

## Key rules
- The builder never reads or writes the DOM of the preview iframe: everything goes through `postMessage` (envelope `{source: 'happy-cms', type, payload}`), origin and source window checked on both sides, never `'*'` as target.
- The builder preview is authorized by a signed URL (UriSigner, kernel secret), never by the admin session on the front.
- Every preview reload asks a new signed URL to the `sylius_happy_cms_admin_page_builder_preview_url` route (DOM event `happy-cms:preview-reload`); never bring back `contentWindow.location.reload()`.
- `SignedPreviewVoter` grants or abstains, never denies: `ContentDocumentVoter` keeps the session-based access.
- The preview URL is built from the route of the entity (host of its channel), never from the current channel context.
- A refused preview is an `AccessDeniedHttpException` without previous exception (plain 403).
- Every preview service can be overridden through its interface (aliases in `config/services.yaml`).
- After an Encore build: `assets:install`; the test application is rebuilt with `yarn install --force`.

## References
| File | Read when |
|---|---|
| `references/decisions.md` | Before changing a behaviour: why it is so |
| `references/business-rules.md` | For any rule or edge case |
| `references/files.md` | To locate the code |
| `references/pitfalls.md` | Before changing anything: mistakes already made |
| `references/sources.md` | To read an original spec again |

For the modelling of a HappyCMS resource, see `sylius:sylius-happy-cms-resource`; this skill only keeps what is specific to the builder.
