# Decisions

## Preview authorized by a signed URL
- **Decision:** the iframe loads a signed, expiring URL (`happy_cms_preview=1`, `_hcms_resource`, `_hcms_id`, `_hcms_locale`, `_hcms_admin_host`, `_expiration`, `_hash`). `SignedPreviewVoter` grants `PAGE_BUILDER` when the signature is valid and resource, id and locale match the subject.
- **Why:** the admin can live on its own host; the browser does not send the admin session to another host.
- **Rejected:** serving the preview from the admin host (route collisions); parent `cookie_domain` (exposes the admin session to the front); preview cookie on the front (third-party cookies blocked in an iframe); authenticator on the preview firewall (every project would have to change its `security.yaml`); token in a header, `/_preview` prefix, `document.domain`; home-made signature.
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## Preview script injected by a response listener
- **Decision:** `PreviewResponseListener` inserts `page-builder-preview.js` before the last `</body>` of a signed response, like the Symfony debug toolbar.
- **Why:** no project template to change.
- **Rejected:** a Twig function to place in the page template (forgetting it would break the builder with no visible error).
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## One message-based path, same host included
- **Decision:** the `postMessage` dialogue is also used when admin and front share their host.
- **Why:** one code path to maintain, tested locally.
- **Rejected:** a dual path (DOM on the same host, messages otherwise).
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## New signed URL on every reload
- **Decision:** before every reload, the builder calls `GET /happy-cms-builder/{resource}/{id}/preview-url?locale=xx` then sets `iframe.src`.
- **Why:** once the signature has expired, the refusal page has no agent; a plain `reload` message would not be enough.
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## Short signature lifetime
- **Decision:** option `sylius_happy_cms.page_builder.preview_url_ttl`, integer in seconds, minimum 1, default 300.
- **Why:** the signature only covers the loading of the main document.
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## Front host taken from the entity route
- **Decision:** URL generated with `router->generate(OBJECT_BASED_ROUTE_NAME, [ROUTE_OBJECT => getOnlineRoute($locale)])`, then completed: `//host/path` gets the scheme of the admin request, `/path` gets the admin scheme and host.
- **Why:** on a separate admin host, the channel context can be wrong; `happy_cms_path_by_id` would return an empty URL for a page of another channel.
- **Rejected:** a `front_base_url` option; HTTPS admin with HTTP front (not a realistic case).
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## Preview refused with a 403
- **Decision:** `RouteRenderService::renderAction()` throws `AccessDeniedHttpException('Access Denied to preview content document')` instead of an `\Exception` (500). Also applies to the session-based preview.
- **Why:** an expired signed URL would fill the production logs.
- **Date:** 2026-11-26 (page-builder-cross-host-preview)

## Builder page opens without the right to sign
- **Decision:** `happy_cms_signed_preview_url()` returns `''` on `PreviewSigningDeniedException`; the builder shows "preview unreachable".
- **Why:** before signed URLs, the page opened with a 403 iframe; throwing while rendering broke the whole page (found in the final review).
- **Date:** 2026-11-26 (page-builder-cross-host-preview, deviation from the spec)

## Id read through ResourceInterface
- **Decision:** signer and voter read the subject id through `ResourceInterface`; without an id, the signer refuses and the voter abstains.
- **Why:** `CmsRoutableInterface` has no `getId()`.
- **Date:** 2026-11-26 (page-builder-cross-host-preview, implementation choice)

## Preview labels in the admin locale, carried by the signed URL
- **Decision:** `PreviewUrlSigner` adds `_hcms_admin_locale` (locale of the admin request); `PreviewLabelsProvider` translates the preview labels and the block type name in that locale (4th argument of `trans()`, request locale untouched), falling back on the request locale.
- **Why:** an editor working in French on an English page must read French labels, right from the first render.
- **Rejected:** page locale (editor reads another language); labels sent by `postMessage` after load (text flashes, the agent would need a map of every block type name).
- **Date:** 2026-11-26 (page-builder-empty-block-placeholder)

## Empty block detected in the browser
- **Decision:** the preview agent measures the content nodes of each wrapper with a `Range` (`getBoundingClientRect().height`), sets `is-empty` under 4px, measures again through `ResizeObserver` (content elements), `MutationObserver` (wrapper subtree) and `resize`.
- **Why:** blocks can be present but invisible (empty JS mount point, element hidden at this breakpoint, popin, tracking script).
- **Rejected:** `strip_tags()` in PHP (misses present but invisible content).
- **Date:** 2026-11-26 (page-builder-empty-block-placeholder)

## Placeholder absolutely positioned, hidden by default
- **Decision:** `wrap()` renders `.content-block-empty-placeholder` (`position: absolute; inset: 0; display: none`) after the content and before the overlays; the agent shows it with `is-empty`. When empty, the hover and state overlays align their button to the right so the name stays readable.
- **Why:** an in-flow placeholder would change the measured height and loop.
- **Date:** 2026-11-26 (page-builder-empty-block-placeholder)

## Minimal height from the edit button
- **Decision:** `min-height: 150px` replaced by `--hcms-block-min-height` (button 44px + 2 x 16px gap) and `--hcms-block-state-min-height` (badge 40px + 12px + button + gaps) for unpublished and deleted blocks; button and badge get a fixed inline height.
- **Why:** a block lower than the button cut it; 150px left large white rectangles.
- **Date:** 2026-11-26 (page-builder-empty-block-placeholder)

## Shared block placeholder shows the type name
- **Decision:** an empty shared block shows "Shared block" like any other type name, not the name of the referenced shared block.
- **Why:** avoids loading the referenced entity in `Helper` (developer's choice).
- **Date:** 2026-11-26 (page-builder-empty-block-placeholder)
## Editor panel width set by the editor
- **Decision:** a handle on the left edge of the editor panel (`role="separator"`, mouse and keyboard) sets `--hcms-editor-width`, from 360 px up to 60 px right of the left edge of the preview content, default 820 px. One value per browser, in `localStorage` (`happy-cms.page-builder.editor-width`).
- **Why:** editors want to see more of the preview while thinking about what to fill in a block, then widen the form again.
- **Rejected:** CSS `resize` (bottom-right corner only); a panel that pushes the preview (the preview width and its responsive breakpoints would change, the block rendering would no longer be faithful); a width per block type or stored server-side (out of scope).
- **Date:** 2026-11-26 (page-builder-resizable-editor-panel)
- **Maximum:** first `viewport - 120 px`; changed after the browser check (PR #182) because the panel then covered the start of the preview. The bound is measured on the preview area, not on the iframe wrapper, so it does not move with the tablet or mobile resolution.
