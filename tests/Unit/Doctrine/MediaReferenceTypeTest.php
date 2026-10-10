<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaReferenceType;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityNotFoundException;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\Article;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\MediaReferenceTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\ReferencedMedia;

final class MediaReferenceTypeTest extends MediaReferenceTestCase
{
    public function testAMediaIsStoredAsItsId(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $stored = $this->entityManager->getConnection()->fetchOne('SELECT image FROM article WHERE id = ?', [$article->id]);

        self::assertSame($media->getId(), (int) $stored);
    }

    public function testAnIdOrANumericStringIsStoredAsAnInteger(): void
    {
        $type = Type::getType(MediaReferenceType::TYPE);
        $platform = new SQLitePlatform();

        self::assertSame(12, $type->convertToDatabaseValue(12, $platform));
        self::assertSame(12, $type->convertToDatabaseValue('12', $platform));
        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertNull($type->convertToDatabaseValue('', $platform));
    }

    public function testTheColumnIsABigint(): void
    {
        self::assertSame('BIGINT', Type::getType(MediaReferenceType::TYPE)->getSQLDeclaration([], new SQLitePlatform()));
    }

    public function testReadingGivesAMediaWithoutQueryingIt(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;

        $article = $this->saveAndReload($article);

        self::assertInstanceOf(ReferencedMedia::class, $article->image);
        self::assertSame($media->getId(), $article->image->getId(), 'The id is known without loading the media.');
        self::assertTrue($this->entityManager->getUnitOfWork()->isUninitializedObject($article->image));
        self::assertSame([], $this->queries->selectsFrom('referenced_media'));
    }

    public function testReadingAFieldLoadsTheMediaWithOneQuery(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;
        $article = $this->saveAndReload($article);

        self::assertSame('Front door', $article->image?->getName());
        self::assertSame('front-door', $article->image?->getPath());
        self::assertCount(1, $this->queries->selectsFrom('referenced_media'));
    }

    public function testListingEntitiesWithSeveralMediaColumnsRunsNoMediaQuery(): void
    {
        $ids = [];
        for ($i = 1; $i <= 5; ++$i) {
            $article = new Article();
            $article->image = $this->saveMedia('Image ' . $i);
            $article->thumbnail = $this->saveMedia('Thumbnail ' . $i);
            $this->entityManager->persist($article);
            $ids[] = $article;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->queries->queries = [];

        $articles = $this->entityManager->getRepository(Article::class)->findAll();

        self::assertCount(5, $articles);
        self::assertSame([], $this->queries->selectsFrom('referenced_media'), 'One find() per media column used to run here.');
    }

    public function testTheMediasOfAListingCanBeLoadedWithASingleQuery(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $article = new Article();
            $article->image = $this->saveMedia('Image ' . $i);
            $this->entityManager->persist($article);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
        $articles = $this->entityManager->getRepository(Article::class)->findAll();
        $this->queries->queries = [];

        $this->entityManager->getRepository(ReferencedMedia::class)->findBy([
            'id' => array_map(static fn (Article $article): ?int => $article->image?->getId(), $articles),
        ]);
        $names = array_map(static fn (Article $article): ?string => $article->image?->getName(), $articles);

        self::assertSame(['Image 1', 'Image 2', 'Image 3', 'Image 4', 'Image 5'], $names);
        self::assertCount(1, $this->queries->selectsFrom('referenced_media'), 'The references are initialized by the batch query.');
    }

    public function testTheSameMediaInTwoColumnsIsTheSameObject(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;
        $article->thumbnail = $media;

        $article = $this->saveAndReload($article);

        self::assertSame($article->image, $article->thumbnail);
    }

    public function testAMediaAlreadyLoadedIsReturnedAsIs(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $loaded = $this->entityManager->find(ReferencedMedia::class, $media->getId());
        $article = $this->entityManager->find(Article::class, $article->id);

        self::assertSame($loaded, $article?->image);
        self::assertFalse($this->entityManager->getUnitOfWork()->isUninitializedObject($loaded));
    }

    public function testAnUnchangedReferenceIsNotWrittenAgain(): void
    {
        $article = new Article();
        $article->image = $this->saveMedia('Front door');
        $article = $this->saveAndReload($article);

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();

        self::assertSame([], $unitOfWork->getEntityChangeSet($article));
    }

    public function testNullStaysNull(): void
    {
        $article = $this->saveAndReload(new Article());

        self::assertNull($article->image);
        self::assertNull($article->thumbnail);
    }

    public function testZeroOrAnEmptyValueReadsAsNull(): void
    {
        $type = Type::getType(MediaReferenceType::TYPE);
        $platform = new SQLitePlatform();

        self::assertNull($type->convertToPHPValue(null, $platform));
        self::assertNull($type->convertToPHPValue(0, $platform));
        self::assertNull($type->convertToPHPValue('0', $platform));
        self::assertNull($type->convertToPHPValue('', $platform));
        self::assertNull($type->convertToPHPValue('not-an-id', $platform));
    }

    public function testTheIdOfADeletedMediaGivesAReferenceThatFailsWhenRead(): void
    {
        $this->entityManager->getConnection()->insert('article', ['image' => 999]);
        $id = (int) $this->entityManager->getConnection()->lastInsertId();

        $article = $this->entityManager->find(Article::class, $id);

        self::assertInstanceOf(MediaInterface::class, $article?->image, 'Checking the media would need the query the type avoids.');
        self::assertSame(999, $article->image->getId());
        $this->expectException(EntityNotFoundException::class);
        $article->image->getName();
    }

    public function testTheIdTypeStillReadsTheRawId(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->legacyImage = $media;

        $article = $this->saveAndReload($article);

        self::assertSame($media->getId(), (int) $article->legacyImage);
    }

    public function testReadingWithoutAReferenceFactoryFails(): void
    {
        MediaReferenceType::setReferenceFactory(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('happy_cms_media_reference_type');

        Type::getType(MediaReferenceType::TYPE)->convertToPHPValue('12', new SQLitePlatform());
    }
}
