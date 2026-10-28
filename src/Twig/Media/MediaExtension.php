<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Media;

use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    /**
     * @param bool $rawMetaValues `sylius_happy_cms.media.raw_meta_values`: the `media_meta` filter returns the metas
     *                            as stored (MediaRuntime::mediaMetaValue()) instead of text only (MediaRuntime::mediaMeta())
     */
    public function __construct(
        protected MediaManager $manager,
        protected FilterManager $filterManager,
        protected bool $rawMetaValues = false,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('resolve_media', [MediaRuntime::class, 'resolveMedia']),
            new TwigFilter('media_infos', [MediaRuntime::class, 'mediaInfos']),
            new TwigFilter('media_meta', [MediaRuntime::class, $this->rawMetaValues ? 'mediaMetaValue' : 'mediaMeta']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('mime_icon', [MediaRuntime::class, 'getMimeIcon']),
            new TwigFunction('file_is_type', [MediaRuntime::class, 'fileIsType']),
            new TwigFunction('happy_cms_media', [MediaRuntime::class, 'media'], ['is_safe' => ['html']]),
            new TwigFunction('happy_cms_media_path', [MediaRuntime::class, 'path']),
            new TwigFunction('happy_cms_media_download_url', [MediaRuntime::class, 'downloadUrl']),
            new TwigFunction('happy_cms_media_csrf_token', [MediaCsrfRuntime::class, 'csrfToken']),
        ];
    }
}
