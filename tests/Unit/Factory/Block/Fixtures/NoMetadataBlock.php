<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Symfony\Component\Form\FormBuilderInterface;

final class NoMetadataBlock extends AbstractBlock
{
    use BlockAttributeMetadataTrait;

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }
}
