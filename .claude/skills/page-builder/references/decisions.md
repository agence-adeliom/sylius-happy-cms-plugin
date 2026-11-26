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
