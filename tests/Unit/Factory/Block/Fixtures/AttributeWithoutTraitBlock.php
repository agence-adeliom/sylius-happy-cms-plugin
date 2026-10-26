<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\FormBuilderInterface;

#[AsBlock(name: 'Ignored', template: 'blocks/ignored.html.twig')]
final class AttributeWithoutTraitBlock extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
    }

    public function getName(): string
    {
        return 'Without trait';
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return 'blocks/without_trait.html.twig';
    }
}
