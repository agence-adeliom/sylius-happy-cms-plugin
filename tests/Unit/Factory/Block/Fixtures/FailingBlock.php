<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

final class FailingBlock extends TemplateBlock
{
    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/failing.html.twig';
    }
}
