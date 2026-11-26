# Pitfalls

- **Refused preview redirects to the shop login instead of a 403** - the security exception listener walks the previous exceptions; an `AccessDeniedException` as `previous` starts the entry point - throw `AccessDeniedHttpException` without `previous`. (page-builder-cross-host-preview, task 8)
- **`X-Frame-Options: sameorigin` always present** - Sylius' `XFrameOptionsSubscriber` sets it on every main response (priority 0) - the preview listener must run after it (priority -128).
- **Iframe stuck on `about:blank`** - `Resources/public` rebuilt but `public/bundles` not updated: the manifest points to a file that returns 404 - run `assets:install` after every build. (manual check, 2026-11-26)
- **`git ls-files resources/public` is empty** - the tracked folder is `Resources/public` (macOS ignores case, git does not): compiled assets are versioned and must be committed with the JS. (final review)
- **Old `BlockEditor.js` in the test application** - `node_modules` holds a copy of the package - `yarn install --force` in `vendor/sylius/test-application`, then `yarn build`.
- **`UriSigner::sign()` with an integer** - an integer is a timestamp, not a duration - pass a date (`clock->now()->modify('+N seconds')`) or a `DateInterval`.
- **Signature refused behind a TLS proxy** - `checkRequest()` signs the scheme and host seen by Symfony - `trusted_proxies` is required.
- **Channel without `hostname` while the admin has its own host** - the URL is completed with the admin host, the agent never answers - "preview unreachable" message after 10 s; fill in the `hostname`.
- **Functional tests with `ADMIN_HOST=admin.localhost` in `.env`** - admin routes require that host; `AdminRoutesTest` and `MateToolsTest` fail (pre-existing) - send admin requests to the `admin_host` parameter, run the suite with `-e ADMIN_HOST=`.
- **Signing a URL in a test without an admin request** - the router context stays `localhost`, the channel route looks like the same host and the URL targets the admin - align `router->getContext()->fromRequest($adminRequest)`.
- **`node --test` prints `MODULE_TYPELESS_PACKAGE_JSON`** - `assets/package.json` has no `type` - left as is: `"type": "module"` would change how webpack handles the whole package.
- **Known, not handled (final review)** - a strict `script-src` blocks the injected script; only the first CSP header is completed; the signed URL is readable by analytics scripts; JS navigation without a click is not detected; the "page has changed" alert is not translated; `parse_str` rewrites `.` and spaces in query keys.
