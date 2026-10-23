<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Block;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Demo of a content block declared with #[AsBlock]: no metadata method.
 */
#[AsBlock(name: 'Demo', icon: 'ti ti-star', template: 'blocks/demo_block.html.twig', position: 10, tab: 'Demo')]
final class DemoBlockType extends AbstractBlock
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['label' => 'Title']);
    }
}
