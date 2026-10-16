## agence-adeliom/sylius-happy-cms-plugin — Agent Instructions

Read-only Mate tools exposing the CMS routes and blocks of the application, as the booted `dev` kernel sees them. They
need `sylius/sylius-mate-extension` (it boots the kernel): call them like the `sylius_*` tools. The easy-crud tools
(`easy_crud_list_admins`, `easy_crud_inspect_admin`) give the aliases and the admin side of the same resources.

| Tool | Use when |
|---|---|
| `happy_cms_match_url` | A front URL answers 404, 500 or the wrong page (`--url=https://host/path/`): matched route, `router` (`cmf` for a CMS route, `symfony` otherwise), controller, parameters and, for a CMS route, the content it serves (class, id, easy-crud `alias`, `online`, `state`, template, controller, routes per locale). On a miss: `trailing_slash_variant` and the CMF `cms_candidates` |
| `happy_cms_list_blocks` | Finding a block (its `block_type` is the value stored in the content JSON), its template or assets; checking a block is registered. `--alias=app.page` lists the blocks the flexible content fields of that resource accept, with `fields` |
| `happy_cms_content_blocks` | A page renders without some blocks, or with a broken one (`--alias=app.page --id=12 --render=true`): the JSON blocks of each flexible content field (`fields.<property>`) with `registered` / `published`, the relational blocks of the page builder (`page_builder`) with their publish state, and per block `render.status` (`rendered`, `empty`, `unpublished`, `unknown_type`, `skipped`, `error` with the exception). `--index=3` adds the settings of one JSON block, `--locale=en_US` reads another translation. Without `--id`: counts the stored block types and lists `unknown_occurrences` (types missing from the registry) |

- Chain them: `happy_cms_match_url` gives `content.alias` and `content.id`, the arguments of `happy_cms_content_blocks`.
- `happy_cms_match_url` runs the router only: the `kernel.request` listeners (redirections, locale) do not run. A URL
  that matches here but redirects in the browser is redirected by such a listener: open it, then read its profile with
  `symfony-profiler-list`.
- `online: false` on the content of a matched CMS route means a 404 outside the page builder preview.
- `render.swallowed_by` names the helper that logged and skipped the failing block: the front shows the page without it.
- Rendering runs the code of the blocks (queries, HTTP clients, caches): call `--render=true` on one entity at a time.
- The rendered blocks of a real request are also in its profile: collector
  `Adeliom\SyliusHappyCMSPlugin\DataCollector\Block\BlockCollector` (and `...\Seo\SeoCollector` for the computed SEO).
- `is_list: false` on a field means its blocks are stored as an object with numbered keys instead of a list.
- `happy_cms_match_url` and `happy_cms_content_blocks` read the database.
