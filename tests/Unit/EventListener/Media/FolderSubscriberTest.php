<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use Adeliom\SyliusHappyCMSPlugin\EventListener\Media\FolderSubscriber;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

/**
 * The storage path the subscriber moves when a folder changes parent or slug. In preUpdate the folder already holds
 * its new values, the old ones come from the change set.
 */
final class FolderSubscriberTest extends TestCase
{
    public function testRenamingANestedFolderMovesItInsideItsParent(): void
    {
        $b = $this->folder('b', $this->folder('a'));
        $folder = $this->folder('d', $b);

        $this->assertMoves([['a/b/c', 'a/b/d']], $folder, ['slug' => ['c', 'd']]);
    }

    public function testRenamingARootFolderMovesItAtTheRoot(): void
    {
        $folder = $this->folder('d');

        $this->assertMoves([['c', 'd']], $folder, ['slug' => ['c', 'd']]);
    }

    public function testMovingAFolderToAnotherParentStillMovesIt(): void
    {
        $a = $this->folder('a');
        $b = $this->folder('b');
        $folder = $this->folder('c', $b);

        $this->assertMoves([['a/c', 'b/c']], $folder, ['parent' => [$a, $b]]);
    }

    public function testMovingAndRenamingAFolderInTheSameFlushMovesItOnce(): void
    {
        $a = $this->folder('a');
        $b = $this->folder('b');
        $folder = $this->folder('d', $b);

        $this->assertMoves([['a/c', 'b/d']], $folder, ['parent' => [$a, $b], 'slug' => ['c', 'd']]);
    }

    public function testMovingARootFolderUnderAParentAndRenamingItMovesItOnce(): void
    {
        $b = $this->folder('b');
        $folder = $this->folder('d', $b);

        $this->assertMoves([['c', 'b/d']], $folder, ['parent' => [null, $b], 'slug' => ['c', 'd']]);
    }

    public function testAnotherChangeMovesNothing(): void
    {
        $this->assertMoves([], $this->folder('c'), ['name' => ['C', 'Renamed']]);
    }

    private function folder(string $slug, ?Folder $parent = null): Folder
    {
        $folder = new Folder();
        $folder->setSlug($slug);
        $folder->setParent($parent);

        return $folder;
    }

    /**
     * @param list<array{string, string}>       $expected
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    private function assertMoves(array $expected, Folder $folder, array $changeSet): void
    {
        $moves = [];
        $manager = $this->createMock(MediaManager::class);
        $manager->method('move')->willReturnCallback(static function (string $oldPath, string $newPath) use (&$moves): void {
            $moves[] = [$oldPath, $newPath];
        });

        (new FolderSubscriber($manager))->preUpdate(
            new PreUpdateEventArgs($folder, $this->createStub(EntityManagerInterface::class), $changeSet),
        );

        self::assertSame($expected, $moves);
    }
}
