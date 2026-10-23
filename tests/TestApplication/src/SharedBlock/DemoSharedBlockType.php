<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\SharedBlock;

use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Demo of a shared block declared with #[AsSharedBlock]: no metadata method.
 */
#[AsSharedBlock(name: 'Demo shared block', icon: 'ti ti-star', template: 'blocks/shared/demo_shared_block.html.twig', description: 'A shared block declared with #[AsSharedBlock]')]
final class DemoSharedBlockType extends AbstractSharedBlockType
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['label' => 'Title']);
    }
}
