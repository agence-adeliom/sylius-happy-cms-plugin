<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Symfony\Component\Form\FormBuilderInterface;

#[AsBlock(name: 'Minimal', template: 'blocks/minimal.html.twig')]
final class MinimalAttributeBlock extends AbstractBlock
{
    use BlockAttributeMetadataTrait;

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }
}
