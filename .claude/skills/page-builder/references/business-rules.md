# Business rules

- A URL signed for a page gives access neither to another page, nor to another resource, nor to another locale (the voter compares the model class of `resource`, the id and `$request->getLocale()`) - `SignedPreviewVoter::vote()`.
- The signature covers scheme, host, path and every parameter: changing the path or any `_hcms_*` invalidates the URL - `UriSigner::checkRequest()` in `SignedPreviewRequestValidator`.
- `_hcms_admin_host` must be an `http(s)://host[:port]` origin, without path, query, fragment or credentials (rebuilt and compared) - `SignedPreviewRequestValidator::isOrigin()`.
- `_hcms_id` must be a positive integer - `SignedPreviewRequestValidator`.
- The validation result is cached on the request (attribute `_hcms_signed_preview`, context or `false`): voter then listener do not compute it twice.
- The signer refuses (`PreviewSigningDeniedException`, no URL) when the current user lacks `PAGE_BUILDER` on the subject.
- `happy_cms_preview=1` is not duplicated when the URL already has it; other query parameters are kept.
- Preview response (main request, 2xx, HTML): always `Cache-Control: private` and `X-Robots-Tag: noindex`. Valid signed one in addition: `X-Frame-Options` removed, `frame-ancestors <admin origin>` added (or appended to the existing CSP; a CSP that already has `frame-ancestors` is left untouched), script inserted before the last `</body>` (case-insensitive). Redirect, JSON, error, sub-request: nothing - `PreviewResponseListener`.
- HTML without `</body>`: headers set, content unchanged.
- Renewal route: `{"url": ...}`, or 403 `{"error": ...}` on `PreviewSigningDeniedException`; locale validated like `builderAction` (`resolveLocale()`).
- Admin side, a `ready` whose `path` differs from the path of the signed URL disables the dialogue until the next load and shows the "The page has changed..." alert; editing a block is then refused - `PreviewBridge::handleMessage()`, `editBlock()`.
- Without `ready` within 10 s (`READY_TIMEOUT_MS`), "preview unreachable" message (`sylius_happy_cms.page_builder.preview_unreachable`); renewal redirected, not JSON or not 200: session expired message (`preview_session_expired`).
- After a reload, the last `scrollY` received is sent back as `restore-scroll` on the next `ready`.
- The preview agent does nothing outside an iframe or without `data-admin-origin`; it blocks links and forms, hides `.sf-toolbar`, only applies `--hcms-*` variables.
- Messages: preview -> admin `ready`, `geometry` (once per frame), `hover`, `edit`; admin -> preview `theme`, `highlight`, `scroll-to-block`, `restore-scroll`. Unknown type or missing `source`: ignored.
- The `block:saved` message (block form iframe) is only accepted from `window.location.origin`.
