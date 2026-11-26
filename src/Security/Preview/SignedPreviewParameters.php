<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security\Preview;

/**
 * Query parameters of a signed page builder preview URL.
 */
final class SignedPreviewParameters
{
    public const PREVIEW = 'happy_cms_preview';

    public const RESOURCE = '_hcms_resource';

    public const ID = '_hcms_id';

    public const LOCALE = '_hcms_locale';

    public const ADMIN_HOST = '_hcms_admin_host';

    public const HASH = '_hash';

    public const EXPIRATION = '_expiration';

    private function __construct()
    {
    }
}
