<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BlockExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            // the context is passed to the block template only when `block_render.inherit_context` is enabled
            new TwigFunction('happy_cms_block_render', [Helper::class, 'renderBlockInContext'], ['is_safe' => ['js', 'html'], 'needs_context' => true]),
            new TwigFunction('happy_cms_block_assets', [Helper::class, 'includeAssets'], ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }
}
