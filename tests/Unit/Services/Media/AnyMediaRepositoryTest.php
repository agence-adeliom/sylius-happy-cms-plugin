<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\AlreadyExist;
use Doctrine\ORM\EntityRepository;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\PlainRepositoryMedia;

/**
 * The media entity of an application may keep its own repository: the media manager works with any
 * Doctrine repository, not only one implementing MediaRepositoryInterface.
 */
final class AnyMediaRepositoryTest extends MediaManagerTestCase
{
    protected function mediaClass(): string
    {
        return PlainRepositoryMedia::class;
    }

    public function testTheMediaRepositoryIsTheRepositoryOfTheEntity(): void
    {
        $repository = $this->manager->getHelper()->getMediaRepository();

        self::assertInstanceOf(EntityRepository::class, $repository);
        self::assertSame(PlainRepositoryMedia::class, $repository->getClassName());
    }

    public function testAMediaIsFoundByItsIdentifier(): void
    {
        $media = $this->saveMedia('door.jpg');

        self::assertSame($media, $this->manager->getMedia((int) $media->getId()));
        self::assertSame($media, $this->manager->getMedia((string) $media->getId()));
        self::assertSame('door.jpg', $this->manager->getHelper()->getPath((int) $media->getId()));
    }

    public function testAMediaWithTheSameNameInTheSameFolderIsRefused(): void
    {
        $this->manager->createMedia($this->pngFile('door.png'), 'products', 'Door');

        $this->expectException(AlreadyExist::class);
        $this->manager->createMedia($this->pngFile('door.png'), 'products', 'Door');
    }
}
