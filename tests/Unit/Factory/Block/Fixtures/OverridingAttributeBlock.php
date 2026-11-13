<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Symfony\Component\Form\FormBuilderInterface;

#[AsBlock(name: 'Static name', icon: 'ti ti-star', template: 'blocks/overriding.html.twig')]
final class OverridingAttributeBlock extends AbstractBlock
{
    use BlockAttributeMetadataTrait;

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }

    public function getName(): string
    {
        return 'Dynamic name';
    }
}
