<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\ManagedFolder;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\ManagedMedia;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * Smoke test of the shared media manager test case: a real MediaManager configured by the plugin
 * extension, on SQLite in memory and a Flysystem in-memory storage.
 */
final class MediaManagerTestCaseTest extends MediaManagerTestCase
{
    public function testTheManagerIsConfiguredByThePluginExtension(): void
    {
        self::assertSame(ManagedMedia::class, $this->parameters['sylius_happy_cms.media.media_entity']);
        self::assertSame(ManagedFolder::class, $this->parameters['sylius_happy_cms.media.folder_entity']);
        self::assertSame($this->projectDir, $this->parameters['kernel.project_dir']);
    }

    public function testAMediaIsCreatedOnTheStorageAndInTheDatabase(): void
    {
        $media = $this->manager->createMedia($this->pngFile('door.png'));

        self::assertInstanceOf(ManagedMedia::class, $media);
        self::assertNotNull($media->getId());
        self::assertSame('door.png', $media->getPath());
        self::assertSame('image/png', $media->getMime());
        self::assertTrue($this->storage->fileExists('door.png'));
        self::assertSame($media, $this->manager->getMedia((int) $media->getId()));
    }

    public function testFoldersAndMediasCanBeSavedDirectly(): void
    {
        $products = $this->saveFolder('products');
        $doors = $this->saveFolder('doors', $products);
        $media = $this->saveMedia('door.jpg', $doors);

        self::assertSame(['products', 'products/doors'], $this->folderPaths());
        self::assertSame('products/doors/door.jpg', $media->getPath());

        $this->entityManager->clear();
        $this->queries->queries = [];
        $this->entityManager->getRepository(ManagedFolder::class)->findOneBy(['slug' => 'doors']);
        self::assertCount(1, $this->queries->selectsFrom('managed_folder'));
    }
}
