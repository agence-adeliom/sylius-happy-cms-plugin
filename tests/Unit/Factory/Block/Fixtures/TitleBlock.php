<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

final class TitleBlock extends TemplateBlock
{
    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/title.html.twig';
    }
}
