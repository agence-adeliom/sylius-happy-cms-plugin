<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Block;

use Adeliom\SyliusHappyCMSPlugin\Block\WysiwygBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Block\DemoBlockType;
use Tests\Adeliom\SyliusHappyCMSPlugin\SharedBlock\DemoFromContentSharedBlockType;
use Tests\Adeliom\SyliusHappyCMSPlugin\SharedBlock\DemoSharedBlockType;

/**
 * The demo blocks of the test application are declared with #[AsBlock] / #[AsSharedBlock] only: they are registered
 * through the autoconfiguration and serve the metadata of their attribute.
 */
final class AttributeBlocksTest extends KernelTestCase
{
    public function testAContentBlockDeclaredWithTheAttributeIsRegistered(): void
    {
        $collection = self::getContainer()->get('sylius.happy_cms.block_collection');
        self::assertInstanceOf(BlockCollection::class, $collection);

        $block = $collection->getBlocks()[DemoBlockType::class] ?? null;
        self::assertInstanceOf(DemoBlockType::class, $block);
        self::assertSame('Demo', $block->getName());
        self::assertSame('ti ti-star', $block->getIcon());
        self::assertSame('blocks/demo_block.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame(10, $block->getPosition());
        self::assertSame('Demo', $block->getTab());
    }

    public function testASharedBlockDeclaredWithTheAttributeIsRegistered(): void
    {
        $collection = self::getContainer()->get('sylius.happy_cms.shared_block.block_collection');
        self::assertInstanceOf(SharedBlockCollection::class, $collection);

        $block = $collection->getBlocks()[DemoSharedBlockType::class] ?? null;
        self::assertInstanceOf(DemoSharedBlockType::class, $block);
        self::assertSame('Demo shared block', $block->getName());
        self::assertSame('blocks/shared/demo_shared_block.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame('A shared block declared with #[AsSharedBlock]', $block->getDescription());
    }

    public function testASharedBlockTakesTheMetadataAndTheFormOfItsContentBlock(): void
    {
        $collection = self::getContainer()->get('sylius.happy_cms.shared_block.block_collection');
        self::assertInstanceOf(SharedBlockCollection::class, $collection);

        $block = $collection->getBlocks()[DemoFromContentSharedBlockType::class] ?? null;
        self::assertInstanceOf(DemoFromContentSharedBlockType::class, $block);
        self::assertSame('Demo', $block->getName());
        self::assertSame('ti ti-star', $block->getIcon());
        self::assertSame('blocks/demo_block.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame(10, $block->getPosition());
        self::assertSame('The demo content block, shared', $block->getDescription());

        $formFactory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);
        $form = $formFactory->create(DemoFromContentSharedBlockType::class, null, ['csrf_protection' => false]);
        self::assertTrue($form->has('title'));
        self::assertSame(DemoFromContentSharedBlockType::class, $form->get('block_type')->getData());
    }

    public function testTheBlocksDeclaredWithMethodsAreUnchanged(): void
    {
        $collection = self::getContainer()->get('sylius.happy_cms.block_collection');
        self::assertInstanceOf(BlockCollection::class, $collection);

        $block = $collection->getBlocks()[WysiwygBlockType::class] ?? null;
        self::assertInstanceOf(WysiwygBlockType::class, $block);
        self::assertSame('sylius_happy_cms.blocks.wysiwyg.name', $block->getName());
        self::assertSame('@SyliusHappyCMSPlugin/front/blocks/wysiwyg_block.html.twig', $block->getFrontEndTemplatePath());
    }
}
