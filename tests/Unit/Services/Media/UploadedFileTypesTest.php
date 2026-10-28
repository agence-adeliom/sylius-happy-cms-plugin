<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\ExtNotAllowed;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\FileValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media\Fixtures\MediaManagerTestCase;

/**
 * Uploaded files: allowed types (configurable lists) and the MIME type / extension kept on the media
 * (content detection by default, client MIME type or original extension as options).
 */
final class UploadedFileTypesTest extends MediaManagerTestCase
{
    public function testTheDefaultListsAreUnchanged(): void
    {
        self::assertSame(FileValidator::DEFAULT_ALLOWED_MIME_TYPES, $this->parameters['sylius_happy_cms.media.allowed_mime_types']);
        self::assertSame(FileValidator::DEFAULT_ALLOWED_EXTENSIONS, $this->parameters['sylius_happy_cms.media.allowed_extensions']);
        self::assertSame('content', $this->parameters['sylius_happy_cms.media.mime_type_source']);
        self::assertFalse($this->parameters['sylius_happy_cms.media.keep_original_extension']);
        self::assertContains('image/png', FileValidator::DEFAULT_ALLOWED_MIME_TYPES);
        self::assertNotContains('application/json', FileValidator::DEFAULT_ALLOWED_MIME_TYPES);
    }

    public function testATypeOutsideTheDefaultListsIsRefused(): void
    {
        $this->expectException(ExtNotAllowed::class);

        $this->manager->createMedia($this->upload('data.json', '{"doors": [1, 2]}', 'application/json'));
    }

    public function testTheAllowedTypesAreConfigurable(): void
    {
        $this->manager = $this->createManager([
            'allowed_mime_types' => [...FileValidator::DEFAULT_ALLOWED_MIME_TYPES, 'application/json'],
            'allowed_extensions' => [...FileValidator::DEFAULT_ALLOWED_EXTENSIONS, 'json'],
        ]);

        $media = $this->manager->createMedia($this->upload('data.json', '{"doors": [1, 2]}', 'application/json'));

        self::assertSame('data.json', $media->getPath());
        self::assertSame('application/json', $media->getMime());
    }

    public function testDangerousExtensionsStayRefusedWhateverTheConfiguration(): void
    {
        $this->manager = $this->createManager([
            'allowed_mime_types' => ['text/x-php', 'text/plain'],
            'allowed_extensions' => ['php'],
        ]);

        $this->expectException(ExtNotAllowed::class);

        $this->manager->createMedia($this->upload('shell.php', '<?php echo 1;', 'text/plain'));
    }

    public function testByDefaultTheMimeTypeAndTheExtensionComeFromTheContent(): void
    {
        $media = $this->manager->createMedia($this->upload('photo.jpg', $this->pngBytes(), 'image/jpeg'));

        self::assertSame('image/png', $media->getMime());
        self::assertSame('photo.png', $media->getSlug());
    }

    public function testTheClientMimeTypeCanBeKept(): void
    {
        $this->manager = $this->createManager(['mime_type_source' => 'client']);

        $photo = $this->manager->createMedia($this->upload('photo.jpg', $this->pngBytes(), 'image/jpeg'));
        $prices = $this->manager->createMedia($this->upload('prices.csv', "door,price\nfront,1200\n", 'text/csv'));

        self::assertSame('image/jpeg', $photo->getMime());
        self::assertSame('photo.jpeg', $photo->getSlug());
        self::assertSame('text/csv', $prices->getMime());
        self::assertSame('prices.csv', $prices->getSlug());
    }

    public function testTheOriginalExtensionCanBeKept(): void
    {
        $this->manager = $this->createManager(['keep_original_extension' => true]);

        $photo = $this->manager->createMedia($this->upload('Photo.JPG', $this->pngBytes(), 'image/jpeg'));
        $prices = $this->manager->createMedia($this->upload('prices.csv', "door,price\nfront,1200\n", 'text/csv'));
        $local = $this->manager->createMedia($this->pngFile('scan.jpg'));

        self::assertSame('photo.jpg', $photo->getSlug());
        self::assertSame('image/png', $photo->getMime());
        self::assertSame('prices.csv', $prices->getSlug());
        self::assertSame('scan.jpg', $local->getSlug());
    }

    private function upload(string $clientName, string $content, string $clientMimeType): UploadedFile
    {
        $dir = $this->projectDir . '/uploads/' . bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        $path = $dir . '/' . $clientName;
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName, $clientMimeType, null, true);
    }

    private function pngBytes(): string
    {
        return (string) file_get_contents($this->pngFile('source-' . bin2hex(random_bytes(4)) . '.png'));
    }
}
