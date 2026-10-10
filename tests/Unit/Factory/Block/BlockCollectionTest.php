<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentEditableInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures\RestrictedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form\Block\Fixtures\TitleBlock;

final class BlockCollectionTest extends TestCase
{
    private BlockCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new BlockCollection([$this->block(TitleBlock::class), $this->block(RestrictedBlock::class)]);
    }

    public function testTheAllowListFiltersTheBlocksOfAResourceThatIsNotContentEditable(): void
    {
        $blocks = $this->collection->getAllowedBlocks([RestrictedBlock::class], $this->createStub(ResourceInterface::class));

        self::assertSame([RestrictedBlock::class], array_keys($blocks));
    }

    public function testAllBlocksAreAllowedForAResourceThatIsNotContentEditableWithoutAllowList(): void
    {
        $blocks = $this->collection->getAllowedBlocks(null, $this->createStub(ResourceInterface::class));

        self::assertSame([TitleBlock::class, RestrictedBlock::class], array_keys($blocks));
    }

    public function testBlocksStillDecideForAContentEditableResource(): void
    {
        $blocks = $this->collection->getAllowedBlocks(null, $this->createStub(ContentEditableInterface::class));

        self::assertSame([TitleBlock::class], array_keys($blocks));
    }

    public function testTheAllowListAndTheBlocksBothApplyToAContentEditableResource(): void
    {
        $blocks = $this->collection->getAllowedBlocks([RestrictedBlock::class], $this->createStub(ContentEditableInterface::class));

        self::assertSame([], $blocks);
    }

    public function testWithoutResourceTheBlocksAreAskedWithNull(): void
    {
        $blocks = $this->collection->getAllowedBlocks([], null);

        self::assertSame([TitleBlock::class, RestrictedBlock::class], array_keys($blocks));
    }

    /**
     * @param class-string<AbstractBlock> $class
     */
    private function block(string $class): AbstractBlock
    {
        return new $class(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(FormFactoryInterface::class),
            new Packages(),
        );
    }
}
