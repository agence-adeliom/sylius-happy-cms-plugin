# Files

| Path | Role |
|---|---|
| `src/Security/Preview/SignedPreviewParameters.php` | Names of the signed parameters |
| `src/Security/Preview/PreviewUrlSigner.php` | Checks the right, completes the URL, adds `_hcms_*`, signs with an expiration (interface `PreviewUrlSignerInterface`) |
| `src/Security/Preview/PreviewUrlGenerator.php` | URL from the online route of the entity, then signature (interface `PreviewUrlGeneratorInterface`) |
| `src/Security/Preview/SignedPreviewRequestValidator.php` | Validates a signed request, caches the result (interface `SignedPreviewRequestValidatorInterface`) |
| `src/Security/Preview/SignedPreviewContext.php` | Read-only context: resource, id, locale, adminOrigin |
| `src/Security/Preview/PreviewSigningDeniedException.php` | Refusal to sign (extends `AccessDeniedException`) |
| `src/Security/SignedPreviewVoter.php` | Grants `PAGE_BUILDER` on signature |
| `src/Security/ContentDocumentVoter.php` | Session-based access (`ROLE_HAPPY_CMS_CONTENT_BUILDER`) |
| `src/Security/PreviewRequestMatcher.php` | Recognises a preview request (`happy_cms_preview=1`) |
| `src/EventListener/PreviewResponseListener.php` | Preview headers and script injection (priority -128) |
| `src/Twig/PreviewUrlExtension.php` | `happy_cms_signed_preview_url()` function |
| `src/Services/Cmf/RouteRenderService.php` | Preview access check, 403 refusal |
| `src/Controller/PageBuilder/PageBuilderController.php` | `builderAction`, `previewUrlAction`, `resolveLocale()` |
| `src/DependencyInjection/Configuration.php` | `page_builder.preview_url_ttl` option |
| `config/services.yaml` | Preview services and aliases per interface |
| `config/routes/admin.yaml` | `sylius_happy_cms_admin_page_builder_preview_url` route |
| `templates/admin/page_builder/index.html.twig` | `data-iframe-url`, `data-preview-url-endpoint`, translated messages |
| `templates/admin/page_builder/content.html.twig` | Preview iframe, `role="alert"` message region, editor panel resize handle |
| `assets/page-builder/page-builder.css` | Builder styles; panel width `var(--hcms-editor-width, 820px)`, `.page-builder.is-resizing` |
| `assets/page-builder/preview-protocol.js` | Envelope, filter (`isAcceptedMessage`), sending (`postToWindow`) |
| `assets/page-builder/preview-agent.js` | Front-side agent (Encore entry `page-builder-preview`) |
| `assets/page-builder/preview-bridge.js` | Admin-side bridge (`PreviewBridge`: load, reload, send) |
| `assets/page-builder/page-builder.js` | Builder: handles, hover, edit, theme through the bridge |
| `assets/controllers/BlockEditor.js` | Dispatches `happy-cms:preview-reload` after a save |
| `assets/page-builder/editor-resize.js` | Editor panel resize: bounds, `localStorage`, pointer and keyboard (`initEditorResize`) |
| `assets/page-builder/tests/` | `node --test` tests of the protocol, the bridge and the panel resize |
| `src/Asset/AssetHappyCMSPackage.php` | Asset package `sylius.happy.cms.assets.package` (manifest of `Resources/public`) |
| `tests/Support/PreviewPageFactory.php` | Channel, published pages and admin for the functional tests |
| `tests/Functional/Controller/PageBuilder/` | End-to-end tests of the signed preview and the renewal route |
| `src/Factory/ContentBlock/PreviewOverlayRenderer.php` | Preview wrapper: state and hover overlays, edit button, empty block placeholder, `data-block-name` |
| `src/Factory/ContentBlock/PreviewLabels.php` | Read-only preview texts with English defaults |
| `src/Factory/ContentBlock/PreviewLabelsProvider.php` | Translates labels and block names in the admin locale (interface `PreviewLabelsProviderInterface`) |
| `src/Factory/ContentBlock/Helper.php` | `renderContentBlock()` wraps the block in preview mode with name, icon and labels |
| `config/services/block.yaml` | `Helper` arguments and `PreviewLabelsProvider` service |
| `assets/page-builder/preview-empty-block.js` | Pure functions: `contentNodesOf()`, `isEmptyHeight()`, `RESERVED_CLASSES` |
| `translations/messages.{en,fr}.yaml` | `sylius_happy_cms.page_builder.preview_overlay.*` keys |
