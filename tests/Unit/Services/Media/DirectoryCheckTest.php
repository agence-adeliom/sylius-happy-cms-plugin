<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * Folders are looked up on the Flysystem storage, whatever the base_url (CDN, front domain) and the adapter.
 */
final class DirectoryCheckTest extends MediaManagerTestCase
{
    protected function mediaConfig(): array
    {
        return ['base_url' => 'https://cdn.example.com/media/download'];
    }

    public function testDirectoryExistsAsksTheStorageAndNotTheLocalDisk(): void
    {
        mkdir($this->projectDir . '/public/media/download/products/windows', 0777, true);
        $this->storage->createDirectory('products/doors');

        self::assertTrue($this->manager->directoryExists('products/doors'));
        self::assertTrue($this->manager->directoryExists('/products//doors/'));
        self::assertFalse($this->manager->directoryExists('products/windows'));
    }

    public function testCreatingMediasInTheSameNestedPathReusesTheFolders(): void
    {
        $first = $this->manager->createMedia($this->pngFile('first.png'), 'products/doors');
        $second = $this->manager->createMedia($this->pngFile('second.png'), 'products/doors');

        self::assertSame(['products', 'products/doors'], $this->folderPaths());
        self::assertSame($first->getFolder(), $second->getFolder());
        self::assertSame('products/doors/second.png', $second->getPath());
        self::assertTrue($this->storage->fileExists('products/doors/second.png'));
    }

    public function testFolderByPathFindsAFolderOfTheStorage(): void
    {
        $products = $this->manager->createFolder('products');
        $doors = $this->manager->createFolder('doors', 'products');

        self::assertSame($doors, $this->manager->folderByPath('products/doors'));
        self::assertSame($products, $this->manager->folderByPath('products'));
        self::assertFalse($this->manager->folderByPath('products/windows'));
    }

    public function testMovingAFolderMovesItsFilesAndSubFoldersInTheStorage(): void
    {
        $this->manager->createMedia($this->pngFile('door.png'), 'products/doors');
        $this->manager->createMedia($this->pngFile('handle.png'), 'products/doors/handles');

        $this->manager->move('products/doors', 'archives/doors');

        self::assertTrue($this->storage->fileExists('archives/doors/door.png'));
        self::assertTrue($this->storage->fileExists('archives/doors/handles/handle.png'));
        self::assertFalse($this->storage->fileExists('products/doors/door.png'));
        self::assertFalse($this->storage->directoryExists('products/doors'));
        self::assertTrue($this->storage->directoryExists('products'));
    }

    public function testMovingAFolderNeverRenamesTheDirectoryItself(): void
    {
        // S3, GCS…: move() copies one key, it cannot rename a directory.
        $this->storage = $this->createMock(FilesystemOperator::class);
        $this->storage->method('fileExists')->willReturn(false);
        $this->storage->method('directoryExists')->willReturn(true);
        $this->storage->method('listContents')->with('products/doors', true)->willReturn(new DirectoryListing([
            new FileAttributes('products/doors/door.png'),
        ]));
        $this->storage->expects(self::once())->method('move')->with('products/doors/door.png', 'archives/doors/door.png');
        $this->storage->expects(self::once())->method('deleteDirectory')->with('products/doors');

        $this->createManager($this->mediaConfig())->move('products/doors', 'archives/doors');
    }

    public function testMovingAFileMovesItInOneCall(): void
    {
        $media = $this->manager->createMedia($this->pngFile('door.png'), 'products');

        $this->manager->move((string) $media->getPath(), 'archives/door.png');

        self::assertTrue($this->storage->fileExists('archives/door.png'));
        self::assertFalse($this->storage->fileExists('products/door.png'));
    }
}
