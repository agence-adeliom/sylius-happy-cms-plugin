# Upgrade

## Unreleased (2.3.x)

### `happy_cms_block_render()`: template context, blocks without `block_published`, render errors

Nothing changes unless you enable the new options (`sylius_happy_cms.block_render`, all `false` by default):

- `inherit_context`: the block templates receive the context of the calling template (overridden by the block
  variables and by the third argument), like the rendering functions of EasyEditor did;
- `missing_published_means_published`: an entry without a `block_published` key is rendered (it was skipped);
- `swallow_render_errors`: a block that fails to render is logged and skipped instead of failing the page.

See [docs/blocks.md](docs/blocks.md#blocks-stored-as-json-flexible-content-field).

Related changes:

- `happy_cms_block_render()` is now declared with `needs_context` and calls the new
  `Helper::renderBlockInContext(array $context, array $data, bool $preview = false, array $extra = [])`. Templates
  call it with the same arguments as before. If you registered your own Twig function on
  `Helper::renderBlock()`, it keeps working (same signature); the publication and error options apply to it too.
- The `Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper` service gets its arguments by name and four new optional
  constructor arguments (`$logger`, `$inheritContext`, `$missingPublishedMeansPublished`, `$swallowRenderErrors`).
  A project that redefines the service with positional arguments keeps the defaults.
