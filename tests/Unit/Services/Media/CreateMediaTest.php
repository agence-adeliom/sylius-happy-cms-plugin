<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Symfony\Component\HttpFoundation\File\File;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * MediaManager::createMedia(), the public API to add a media from application code
 * (imports, fixtures, forms): source, folder (by path or entity), name and metas.
 */
final class CreateMediaTest extends MediaManagerTestCase
{
    private const PNG_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function testItCreatesAMediaFromALocalFileInAFolderPath(): void
    {
        $media = $this->manager->createMedia($this->pngFile('front-door.png'), 'products/doors');

        self::assertSame('front-door.png', $media->getName());
        self::assertSame('products/doors/front-door.png', $media->getPath());
        self::assertSame('image/png', $media->getMime());
        self::assertSame(['width' => 1, 'height' => 1, 'ratio' => 100], $media->getMetas()['dimensions'] ?? null);
        self::assertNotNull($media->getId());
        self::assertTrue($this->storage->fileExists('products/doors/front-door.png'));
    }

    public function testItCreatesAMediaFromAFileObjectWithAName(): void
    {
        $media = $this->manager->createMedia(new File($this->pngFile('IMG_0001.png')), null, 'Front door');

        self::assertSame('Front door', $media->getName());
        self::assertSame('front-door.png', $media->getPath());
    }

    public function testItCreatesAMediaInAGivenFolder(): void
    {
        $doors = $this->manager->createFolder('doors', 'products');
        self::assertNotNull($doors);

        $media = $this->manager->createMedia($this->pngFile('door.png'), folder: $doors);

        self::assertSame($doors, $media->getFolder());
        self::assertSame('products/doors/door.png', $media->getPath());
        self::assertSame(['products', 'products/doors'], $this->folderPaths());
    }

    public function testAGivenFolderWinsOverThePath(): void
    {
        $doors = $this->manager->createFolder('doors');
        self::assertNotNull($doors);

        $media = $this->manager->createMedia($this->pngFile('door.png'), 'elsewhere', folder: $doors);

        self::assertSame('doors/door.png', $media->getPath());
        self::assertSame(['doors'], $this->folderPaths());
    }

    public function testItAddsTheGivenMetas(): void
    {
        $media = $this->manager->createMedia($this->pngFile('door.png'), 'products', metas: ['alt' => 'A front door', 'title' => 'Door']);

        self::assertSame('A front door', $media->getMeta('alt'));
        self::assertSame('Door', $media->getMeta('title'));
        self::assertArrayHasKey('dimensions', $media->getMetas());

        $this->entityManager->clear();
        $reloaded = $this->manager->getMedia((int) $media->getId());
        self::assertNotNull($reloaded);
        self::assertSame('A front door', $reloaded->getMeta('alt'));
    }

    public function testABase64SourceKeepsTheGivenName(): void
    {
        $media = $this->manager->createMedia(self::PNG_BASE64, 'drawings', 'Hello world');

        self::assertSame('Hello world', $media->getName());
        self::assertSame('drawings/hello-world.png', $media->getPath());
        self::assertTrue($this->storage->fileExists('drawings/hello-world.png'));
    }

    public function testABase64SourceWithoutNameGetsARandomName(): void
    {
        $media = $this->manager->createMedia(self::PNG_BASE64);

        self::assertNotEmpty($media->getName());
        self::assertStringEndsWith('.png', (string) $media->getSlug());
    }
}
