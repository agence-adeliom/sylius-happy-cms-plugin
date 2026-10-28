<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * MediaHelper::getMediaByPath(), behind the public download route: a targeted lookup by the slugs
 * of the path instead of loading every media.
 */
final class MediaByPathTest extends MediaManagerTestCase
{
    public function testItFindsAMediaByItsPath(): void
    {
        $products = $this->saveFolder('products');
        $doors = $this->saveFolder('doors', $products);
        $media = $this->saveMedia('door.jpg', $doors);
        $this->saveMedia('door.jpg', $products);
        $root = $this->saveMedia('logo.png');

        self::assertSame($media, $this->manager->getHelper()->getMediaByPath('products/doors/door.jpg'));
        self::assertSame($media, $this->manager->getHelper()->getMediaByPath('/products//doors/door.jpg'));
        self::assertSame($root, $this->manager->getHelper()->getMediaByPath('logo.png'));
    }

    public function testAnUnknownPathGivesNull(): void
    {
        $products = $this->saveFolder('products');
        $this->saveMedia('door.jpg', $products);

        self::assertNull($this->manager->getHelper()->getMediaByPath('products/window.jpg'));
        self::assertNull($this->manager->getHelper()->getMediaByPath('other/door.jpg'));
        self::assertNull($this->manager->getHelper()->getMediaByPath('door.jpg'));
        self::assertNull($this->manager->getHelper()->getMediaByPath(''));
    }

    public function testDuplicatedSlugsResolveToTheFirstMedia(): void
    {
        // Two folders with the same slug under the same parent, and two media with the same slug in a folder.
        $products = $this->saveFolder('products');
        $this->saveFolder('doors', $products);
        $doors = $this->saveFolder('doors', $products);
        $first = $this->saveMedia('door.jpg', $doors);
        $this->saveMedia('door.jpg', $doors);

        self::assertSame($first, $this->manager->getHelper()->getMediaByPath('products/doors/door.jpg'));
    }

    public function testAcrossDuplicatedFoldersTheMediaWithTheLowestIdWins(): void
    {
        $products = $this->saveFolder('products');
        $firstDoors = $this->saveFolder('doors', $products);
        $secondDoors = $this->saveFolder('doors', $products);
        $first = $this->saveMedia('door.jpg', $secondDoors);
        $this->saveMedia('door.jpg', $firstDoors);

        self::assertSame($first, $this->manager->getHelper()->getMediaByPath('products/doors/door.jpg'));
    }

    public function testItDoesNotLoadEveryMedia(): void
    {
        $products = $this->saveFolder('products');
        foreach (range(1, 20) as $i) {
            $this->saveMedia(sprintf('door-%d.jpg', $i), $products);
        }
        $this->entityManager->clear();
        $this->queries->queries = [];

        $media = $this->manager->getHelper()->getMediaByPath('products/door-7.jpg');

        self::assertNotNull($media);
        self::assertSame('door-7.jpg', $media->getSlug());
        $mediaSelects = $this->queries->selectsFrom('managed_media');
        self::assertNotEmpty($mediaSelects);
        foreach ($mediaSelects as $sql) {
            self::assertStringContainsString('WHERE', $sql);
        }
        self::assertCount(1, $this->entityManager->getUnitOfWork()->getIdentityMap()[$media::class] ?? []);
    }
}
