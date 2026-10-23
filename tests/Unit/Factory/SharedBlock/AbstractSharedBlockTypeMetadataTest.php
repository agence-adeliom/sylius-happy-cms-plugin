<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\AttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\MinimalAttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\NoMetadataSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\OverridingAttributeSharedBlock;

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
}
