<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\ManagedFolder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * A media library whose public URL is absolute (CDN, front domain) or whose storage is not the local
 * public directory: folders are looked up on the storage or in the database, not on the local disk.
 */
final class DirectoryCheckTest extends MediaManagerTestCase
{
    protected function mediaConfig(): array
    {
        return [
            'base_url' => 'https://cdn.example.com/media/download',
            'directory_check' => 'storage',
        ];
    }

    public function testThePublicDirectoryCheckStaysTheDefault(): void
    {
        $this->createManager([]);

        self::assertSame('public_dir', $this->parameters['sylius_happy_cms.media.directory_check']);
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

        self::assertTrue($this->manager->directoryExists('products/doors'));
        self::assertSame($doors, $this->manager->folderByPath('products/doors'));
        self::assertSame($products, $this->manager->folderByPath('products'));
        self::assertFalse($this->manager->folderByPath('products/windows'));
    }

    public function testMovingAFolderMovesItsFilesInTheStorage(): void
    {
        $this->manager->createMedia($this->pngFile('door.png'), 'products/doors');
        $this->manager->createMedia($this->pngFile('handle.png'), 'products/doors/handles');

        $this->manager->move('products/doors', 'archives/doors');

        self::assertTrue($this->storage->fileExists('archives/doors/door.png'));
        self::assertTrue($this->storage->fileExists('archives/doors/handles/handle.png'));
        self::assertFalse($this->storage->fileExists('products/doors/door.png'));
        self::assertFalse($this->storage->directoryExists('products/doors'));
    }

    public function testTheDatabaseCheckLooksForTheFolderRows(): void
    {
        $this->manager = $this->createManager([
            'base_url' => 'https://cdn.example.com/media/download',
            'directory_check' => 'database',
        ]);
        // Folder rows without any directory on the storage (prefix-only object storages).
        $products = $this->saveFolder('products');
        $doors = $this->saveFolder('doors', $products);

        self::assertFalse($this->storage->directoryExists('products/doors'));
        self::assertTrue($this->manager->directoryExists('products/doors'));
        self::assertTrue($this->manager->directoryExists(''));
        self::assertFalse($this->manager->directoryExists('products/windows'));
        self::assertSame($doors, $this->manager->folderByPath('products/doors'));

        $media = $this->manager->createMedia($this->pngFile('door.png'), 'products/doors');
        self::assertSame($doors, $media->getFolder());
        self::assertCount(2, $this->entityManager->getRepository(ManagedFolder::class)->findAll());
    }
}
