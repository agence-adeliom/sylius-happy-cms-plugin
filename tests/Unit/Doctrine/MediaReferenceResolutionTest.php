<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine;

use Adeliom\SyliusHappyCMSPlugin\Doctrine\MediaReferenceType;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\Routing\RouterInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\Article;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\MediaReferenceTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\ReferencedMedia;

final class MediaReferenceResolutionTest extends MediaReferenceTestCase
{
    public function testTheBundleGivesTheTypeReferencesToTheConfiguredMediaEntity(): void
    {
        $media = $this->saveMedia('Front door');
        $this->entityManager->clear();
        $this->queries->queries = [];
        MediaReferenceType::setReferenceFactory(null);

        $this->bootPlugin();
        $reference = Type::getType(MediaReferenceType::TYPE)->convertToPHPValue((string) $media->getId(), new SQLitePlatform());

        self::assertInstanceOf(ReferencedMedia::class, $reference);
        self::assertTrue($this->entityManager->getUnitOfWork()->isUninitializedObject($reference));
        self::assertSame('Front door', $reference->getName());
    }

    public function testShuttingTheBundleDownRemovesTheReferenceFactory(): void
    {
        $plugin = $this->bootPlugin();

        $plugin->shutdown();

        $this->expectException(\LogicException::class);
        Type::getType(MediaReferenceType::TYPE)->convertToPHPValue('12', new SQLitePlatform());
    }

    public function testTheMediaHelperLoadsAReference(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();
        $article->image = $media;
        $article = $this->saveAndReload($article);
        self::assertNotNull($article->image);

        $resolved = $this->mediaHelper()->getMedia($article->image);

        self::assertSame($article->image, $resolved);
        self::assertFalse($this->entityManager->getUnitOfWork()->isUninitializedObject($resolved));
        self::assertSame('front-door', $this->mediaHelper()->getPath($article->image));
    }

    public function testTheMediaHelperResolvesTheReferenceToADeletedMediaToNull(): void
    {
        $this->entityManager->getConnection()->insert('article', ['image' => 999]);
        $article = $this->entityManager->find(Article::class, (int) $this->entityManager->getConnection()->lastInsertId());
        self::assertNotNull($article?->image);

        self::assertNull($this->mediaHelper()->getMedia($article->image), 'Same result as for the unknown id 999.');
        self::assertNull($this->mediaHelper()->getMedia(999));
        self::assertNull($this->mediaHelper()->getPath($article->image));
    }

    private function bootPlugin(): SyliusHappyCMSPlugin
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturnMap([[ReferencedMedia::class, $this->entityManager]]);
        $container = new Container();
        $container->setParameter('sylius_happy_cms.media.media_entity', ReferencedMedia::class);
        $container->set('doctrine', $registry);

        $plugin = new SyliusHappyCMSPlugin();
        $plugin->setContainer($container);
        $plugin->boot();

        return $plugin;
    }

    private function mediaHelper(): MediaHelper
    {
        $container = new Container();
        $container->setParameter('sylius_happy_cms.media.media_entity', ReferencedMedia::class);

        return new MediaHelper(new ContainerBag($container), $this->entityManager, $this->createStub(RouterInterface::class));
    }
}
