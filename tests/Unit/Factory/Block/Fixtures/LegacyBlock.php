<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\FormBuilderInterface;

final class LegacyBlock extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }

    public function getName(): string
    {
        return 'Legacy';
    }

    public function getIcon(): string
    {
        return 'ti ti-file';
    }

    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/legacy.html.twig';
    }
}
