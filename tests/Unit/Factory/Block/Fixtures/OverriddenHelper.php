<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Twig\Markup;

/**
 * A project helper overriding renderBlock(), as written before renderBlockInContext() existed.
 */
final class OverriddenHelper extends Helper
{
    public function renderBlock(array $data, bool $preview = false, array $extra = []): ?Markup
    {
        return new Markup('[overridden ' . parent::renderBlock($data, $preview, $extra) . ']', 'UTF-8');
    }
}
