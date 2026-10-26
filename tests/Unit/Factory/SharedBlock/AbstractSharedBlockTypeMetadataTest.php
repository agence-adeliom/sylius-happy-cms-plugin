<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\AttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\MinimalAttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\NoMetadataSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\OverridingAttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\SharedFromContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\SharedFromContentBlockWithOwnValues;

final class AbstractSharedBlockTypeMetadataTest extends TestCase
{
    public function testTheMetadataIsReadFromTheAttribute(): void
    {
        $block = new AttributeSharedBlock($this->createStub(EntityManagerInterface::class));

        self::assertSame('Newsletter', $block->getName());
        self::assertSame('ti ti-mail', $block->getIcon());
        self::assertSame('blocks/shared/newsletter.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame(5, $block->getPosition());
        self::assertSame('Newsletter subscription form', $block->getDescription());
    }

    public function testTheOptionalValuesKeepTheirDefaults(): void
    {
        $block = new MinimalAttributeSharedBlock($this->createStub(EntityManagerInterface::class));

        self::assertSame('', $block->getIcon());
        self::assertSame(100, $block->getPosition());
        self::assertSame('', $block->getDescription());
    }

    public function testAnOverriddenMethodWinsOverTheAttribute(): void
    {
        $block = new OverridingAttributeSharedBlock($this->createStub(EntityManagerInterface::class));

        self::assertSame('Dynamic description', $block->getDescription());
        self::assertSame('Overriding', $block->getName());
    }

    public function testABlockWithoutAttributeNorMethodFailsExplicitly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" has no metadata for getFrontEndTemplatePath(): set #[AsSharedBlock] on the class or override getFrontEndTemplatePath().', NoMetadataSharedBlock::class));

        (new NoMetadataSharedBlock($this->createStub(EntityManagerInterface::class)))->getFrontEndTemplatePath();
    }

    public function testASharedBlockTakesTheMetadataAndTheFormOfItsContentBlock(): void
    {
        $block = new SharedFromContentBlock($this->createStub(EntityManagerInterface::class));
        $block->setSharedBlockSource($this->titleContentBlock());

        self::assertSame('Title block', $block->getName());
        self::assertSame('ti ti-h1', $block->getIcon());
        self::assertSame('blocks/title.html.twig', $block->getFrontEndTemplatePath());
        self::assertSame(7, $block->getPosition());
        self::assertSame('Taken from a content block', $block->getDescription());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())->method('add')->with('title', TextType::class);
        $block->buildBlock($builder, []);
    }

    public function testTheValuesOfTheAttributeWinOverTheContentBlock(): void
    {
        $block = new SharedFromContentBlockWithOwnValues($this->createStub(EntityManagerInterface::class));
        $block->setSharedBlockSource($this->titleContentBlock());

        self::assertSame('Shared title', $block->getName());
        self::assertSame('ti ti-share', $block->getIcon());
        self::assertSame(3, $block->getPosition());
        self::assertSame('blocks/title.html.twig', $block->getFrontEndTemplatePath());
    }

    public function testASharedBlockWhoseContentBlockWasNotInjectedFailsExplicitly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The shared block "%s" takes the metadata and the form of "%s" (#[AsSharedBlock(block:)]) but this block was not injected', SharedFromContentBlock::class, TitleContentBlock::class));

        (new SharedFromContentBlock($this->createStub(EntityManagerInterface::class)))->getName();
    }

    private function titleContentBlock(): TitleContentBlock
    {
        return new TitleContentBlock(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(FormFactoryInterface::class),
            new Packages(),
        );
    }
}
