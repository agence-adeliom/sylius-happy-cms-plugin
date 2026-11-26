# v2.3.5

## Page builder preview on a front host different from the admin host

The page builder preview is authorized by a signed, expiring URL and driven through `postMessage`: the admin can be
served from its own host. New option `sylius_happy_cms.page_builder.preview_url_ttl`. A refused preview is now a 403.

See [UPGRADE.md](./UPGRADE.md) (2.3.5) and [docs/usage/preview.md](./docs/usage/preview.md).

# v2.3.0

## New block system and brand new content builder

Added ContentBlock entity for modular content management and new page builder interface. 

See [MIGRATION_CONTENT_BLOCKS.md](./MIGRATION_CONTENT_BLOCKS.md) for details.

## Url pattern for preview routes has changed

Instead of managing 2 routes (online and preview), now only online route is used. To get preview mode, just add ?happy_cms_preview=1 in your url.
A DocumentVoter and specific firewall will check if current user is granted to access to preview mode.

Globally all user with ROLE_HAPPY_CMS_CONTENT_BUILDER will be able to manage content and access to preview mode.

! Please reconfigure your security :

Preview routes now require ROLE_HAPPY_CMS_CONTENT_BUILDER instead of ROLE_CMS_PREVIEW.

Please update your `security.yaml` accordingly:

```yaml
security:
    firewalls:
        #... 
        admin_happy_cms_content_builder:
            switch_user: { role: ROLE_ALLOWED_TO_SWITCH }
            context: admin
            pattern: "%sylius.security.shop_regex%"
            request_matcher: Adeliom\SyliusHappyCMSPlugin\Security\PreviewRequestMatcher
            provider: sylius_admin_user_provider
    #... 
    role_hierarchy:
        ROLE_ADMINISTRATION_ACCESS: [ ROLE_HAPPY_CMS_CONTENT_BUILDER ]
```
