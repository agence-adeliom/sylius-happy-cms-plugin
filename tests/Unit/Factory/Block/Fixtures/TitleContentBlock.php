<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

#[AsBlock(name: 'Title block', template: 'blocks/title.html.twig', icon: 'ti ti-h1', position: 7)]
class TitleContentBlock extends AbstractBlock
{
    use BlockAttributeMetadataTrait;

    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class);
    }
}
