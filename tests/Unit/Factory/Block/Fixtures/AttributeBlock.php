<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Symfony\Component\Form\FormBuilderInterface;

#[AsBlock(name: 'Hero', icon: 'ti ti-star', template: 'blocks/hero.html.twig', position: 10, tab: 'Marketing')]
class AttributeBlock extends AbstractBlock
{
    use BlockAttributeMetadataTrait;

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }
}
