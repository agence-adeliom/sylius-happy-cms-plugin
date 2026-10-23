<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\FormBuilderInterface;

final class NoMetadataBlock extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }
}
