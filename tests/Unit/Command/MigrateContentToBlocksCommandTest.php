<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Command;

use Adeliom\SyliusHappyCMSPlugin\Command\MigrateContentToBlocksCommand;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageTranslation;

/**
 * The JSON content stored before the page builder had no notion of an unpublished block: a block without
 * `block_published` key was shown, so it is migrated as a published block.
 */
final class MigrateContentToBlocksCommandTest extends TestCase
{
    public function testABlockWithoutPublishedKeyIsMigratedAsPublished(): void
    {
        $block = $this->migrateBlock(['block_type' => 'App\\Block\\Text', 'title' => 'Hello']);

        self::assertTrue($block->isPublished());
        self::assertSame(['title' => 'Hello'], $block->getPublishedData());
        self::assertSame(['title' => 'Hello'], $block->getDraftData());
    }

    public function testABlockPublishedInItsContentIsMigratedAsPublished(): void
    {
        $block = $this->migrateBlock(['block_type' => 'App\\Block\\Text', 'block_published' => '1', 'title' => 'Hello']);

        self::assertTrue($block->isPublished());
        self::assertSame(['title' => 'Hello'], $block->getPublishedData());
    }

    public function testABlockUnpublishedInItsContentIsMigratedAsUnpublished(): void
    {
        $block = $this->migrateBlock(['block_type' => 'App\\Block\\Text', 'block_published' => '0', 'title' => 'Hello']);

        self::assertFalse($block->isPublished());
        self::assertNull($block->getPublishedData());
        self::assertSame(['title' => 'Hello'], $block->getDraftData());
    }

    /**
     * @param array<string, mixed> $blockData
     */
    private function migrateBlock(array $blockData): ContentBlockInterface
    {
        $translation = new PageTranslation();
        $translation->setLocale('fr_FR');
        $translation->setContent(['block-1' => $blockData]);

        $page = new Page();
        $page->addTranslation($translation);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findAll')->willReturn([$page]);

        $persisted = [];
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->method('persist')->willReturnCallback(static function (object $object) use (&$persisted): void {
            $persisted[] = $object;
        });

        $parameterBag = new ParameterBag([
            'sylius.resources' => ['app.page' => ['classes' => ['model' => Page::class]]],
        ]);

        $tester = new CommandTester(new MigrateContentToBlocksCommand($entityManager, $parameterBag));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        self::assertCount(1, $persisted);
        self::assertInstanceOf(PageContentBlock::class, $persisted[0]);

        return $persisted[0];
    }
}
