<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\AttributeBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\ChildOfAttributeBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\LegacyBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\MinimalAttributeBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\NoMetadataBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\OverridingAttributeBlock;

final class AbstractBlockMetadataTest extends TestCase
{
    public function testTheMetadataIsReadFromTheAttribute(): void
    {
        $block = $this->block(AttributeBlock::class);

        self::assertSame('Hero', $block->getName());
        self::assertSame('ti ti-star', $block->getIcon());
        self::assertSame('blocks/hero.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame(10, $block->getPosition());
        self::assertSame('Marketing', $block->getTab());
    }

    public function testTheOptionalValuesKeepTheirPreviousDefaults(): void
    {
        $block = $this->block(MinimalAttributeBlock::class);

        self::assertSame('', $block->getIcon());
        self::assertSame(100, $block->getPosition());
        self::assertSame('translated:sylius_happy_cms.editor.blocks', $block->getTab());
    }

    public function testAnOverriddenMethodWinsOverTheAttribute(): void
    {
        $block = $this->block(OverridingAttributeBlock::class);

        self::assertSame('Dynamic name', $block->getName());
        self::assertSame('ti ti-star', $block->getIcon());
    }

    public function testABlockExtendingAConcreteBlockInheritsItsAttribute(): void
    {
        self::assertSame('Hero', $this->block(ChildOfAttributeBlock::class)->getName());
    }

    public function testABlockWithMethodsOnlyIsUnchanged(): void
    {
        $block = $this->block(LegacyBlock::class);

        self::assertSame('Legacy', $block->getName());
        self::assertSame(100, $block->getPosition());
        self::assertSame('translated:sylius_happy_cms.editor.blocks', $block->getTab());
    }

    public function testABlockWithoutAttributeNorMethodFailsExplicitly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" has no metadata for getName(): set #[AsBlock] on the class or override getName().', NoMetadataBlock::class));

        $this->block(NoMetadataBlock::class)->getName();
    }

    /**
     * @param class-string<AbstractBlock> $class
     */
    private function block(string $class): AbstractBlock
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => 'translated:' . $id);

        return new $class(
            $this->createStub(EntityManagerInterface::class),
            $translator,
            $this->createStub(FormFactoryInterface::class),
            new Packages(),
        );
    }
}
