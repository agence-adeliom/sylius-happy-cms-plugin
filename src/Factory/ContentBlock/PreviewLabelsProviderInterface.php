<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;

interface PreviewLabelsProviderInterface
{
    /**
     * The preview texts, in the language of the person editing (admin locale of the signed preview URL),
     * else in the language of the current request.
     */
    public function labels(): PreviewLabels;

    /**
     * The name of a block type (a translation key), translated in the same language.
     */
    public function blockName(BlockTypeInterface $block): string;
}
