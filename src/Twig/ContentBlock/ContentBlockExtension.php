<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentBlockExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_content_block_render', [Helper::class, 'renderContentBlock'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('happy_cms_content_block_assets', [Helper::class, 'includeAssets'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
                'needs_context' => true,
            ]),
        ];
    }
}
