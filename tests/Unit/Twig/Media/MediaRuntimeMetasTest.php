<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Twig\Media\MediaRuntime;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Metas are not only strings (dimensions, oEmbed code...): media_meta and happy_cms_media read them as stored.
 */
final class MediaRuntimeMetasTest extends MediaManagerTestCase
{
    private const IFRAME = '<iframe src="https://www.youtube.com/embed/abc" width="200" height="113"></iframe>';

    public function testMediaMetaReturnsNonTextMetasAsStored(): void
    {
        $media = $this->mediaWithMetas('image/jpeg', [
            'alt' => 'A front door',
            'dimensions' => ['width' => 1200, 'height' => 800, 'ratio' => 66.67],
        ]);
        $runtime = $this->runtime();

        self::assertSame(['width' => 1200, 'height' => 800, 'ratio' => 66.67], $runtime->mediaMeta($media, 'dimensions'));
        self::assertSame('A front door', $runtime->mediaMeta($media, 'alt'));
        self::assertSame('none', $runtime->mediaMeta($media, 'title', 'none'));
        self::assertSame('A front door', $runtime->mediaMeta((int) $media->getId(), 'alt'));
    }

    public function testAnOembedMediaRendersItsCode(): void
    {
        $media = $this->mediaWithMetas('application/json+oembed', [
            'title' => 'Our doors',
            'url' => 'https://www.youtube.com/watch?v=abc',
            'code' => ['html' => self::IFRAME, 'width' => 200, 'height' => 113, 'ratio' => 56.5],
        ]);

        $html = $this->runtime()->media($media);

        self::assertStringContainsString(self::IFRAME, $html);
        self::assertStringContainsString('padding-top: 56.5%', $html);
    }

    public function testAnOembedMediaWithATextCodeRendersIt(): void
    {
        $media = $this->mediaWithMetas('application/json+oembed', ['code' => self::IFRAME]);

        self::assertStringContainsString(self::IFRAME, $this->runtime()->media($media));
    }

    /**
     * @param array<string, mixed> $metas
     */
    private function mediaWithMetas(string $mime, array $metas): MediaInterface
    {
        $media = $this->saveMedia('media', null, $mime);
        $media->setMetas($metas);
        $this->entityManager->flush();

        return $media;
    }

    private function runtime(): MediaRuntime
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 4) . '/templates', 'SyliusHappyCMSPlugin');

        return new MediaRuntime(
            $this->manager,
            new Environment($loader, ['strict_variables' => false]),
            $this->createStub(FilterManager::class),
            $this->createStub(CacheManager::class),
        );
    }
}
