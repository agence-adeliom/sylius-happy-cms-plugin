<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Services\Media\FileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileValidatorTest extends TestCase
{
    private FileValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FileValidator();
    }

    /**
     * @test
     */
    public function it_blocks_dangerous_php_extension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous file extension detected: php');

        $file = $this->createMockUploadedFile('malware.php', 'text/plain');
        $this->validator->validate($file);
    }

    /**
     * @test
     */
    public function it_blocks_phtml_extension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous file extension detected: phtml');

        $file = $this->createMockUploadedFile('malware.phtml', 'text/plain');
        $this->validator->validate($file);
    }

    /**
     * @test
     */
    public function it_blocks_phar_extension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous file extension detected: phar');

        $file = $this->createMockUploadedFile('malware.phar', 'text/plain');
        $this->validator->validate($file);
    }

    /**
     * @test
     */
    public function it_blocks_executable_extensions(): void
    {
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'sh', 'asp', 'aspx', 'jsp'];

        foreach ($dangerousExtensions as $ext) {
            try {
                $file = $this->createMockUploadedFile("malware.{$ext}", 'application/octet-stream');
                $this->validator->validate($file);
                $this->fail("Expected exception for .{$ext} file");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Dangerous file extension', $e->getMessage());
            }
        }
    }

    /**
     * @test
     */
    public function it_blocks_double_extensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dangerous double extension detected');

        // Create a real temporary file to test double extension
        $tempFile = $this->createRealImageFile('malware.php.jpg');

        try {
            $file = new UploadedFile(
                $tempFile,
                'malware.php.jpg',
                'image/jpeg',
                null,
                true
            );

            $this->validator->validate($file);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function it_blocks_unlisted_extensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File extension not allowed: xyz');

        $file = $this->createMockUploadedFile('file.xyz', 'text/plain');
        $this->validator->validate($file);
    }

    /**
     * @test
     */
    public function it_validates_jpeg_magic_bytes(): void
    {
        // Create a real JPEG file
        $tempFile = $this->createRealImageFile('test.jpg');

        try {
            $file = new UploadedFile(
                $tempFile,
                'test.jpg',
                'image/jpeg',
                null,
                true
            );

            // Should not throw exception
            $this->validator->validate($file);
            $this->assertTrue(true); // If we get here, validation passed
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function it_detects_real_mime_type(): void
    {
        // Create a real JPEG file
        $tempFile = $this->createRealImageFile('test.jpg');

        try {
            $file = new UploadedFile(
                $tempFile,
                'test.jpg',
                'image/jpeg',
                null,
                true
            );

            $realMimeType = $this->validator->getRealMimeType($file);

            // Should detect image/jpeg from content, not from header
            $this->assertStringStartsWith('image/', $realMimeType);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function it_blocks_fake_image_with_php_content(): void
    {
        // Create a file with PHP content but .jpg extension
        $tempFile = tempnam(sys_get_temp_dir(), 'fake_image_');
        file_put_contents($tempFile, '<?php system($_GET["cmd"]); ?>');
        rename($tempFile, $tempFile . '.jpg');
        $tempFile .= '.jpg';

        try {
            $file = new UploadedFile(
                $tempFile,
                'fake_image.jpg',
                'image/jpeg', // Fake MIME type
                null,
                true
            );

            $this->expectException(\InvalidArgumentException::class);
            $this->validator->validate($file);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function it_allows_valid_pdf(): void
    {
        $tempFile = $this->createRealPdfFile();

        try {
            $file = new UploadedFile(
                $tempFile,
                'document.pdf',
                'application/pdf',
                null,
                true
            );

            // Should not throw exception
            $this->validator->validate($file);
            $this->assertTrue(true);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function it_checks_if_mime_type_is_allowed(): void
    {
        $this->assertTrue($this->validator->isMimeTypeAllowed('image/jpeg'));
        $this->assertTrue($this->validator->isMimeTypeAllowed('application/pdf'));
        $this->assertFalse($this->validator->isMimeTypeAllowed('application/x-php'));
        $this->assertFalse($this->validator->isMimeTypeAllowed('text/x-php'));
    }

    /**
     * @test
     */
    public function it_checks_if_extension_is_allowed(): void
    {
        $this->assertTrue($this->validator->isExtensionAllowed('jpg'));
        $this->assertTrue($this->validator->isExtensionAllowed('pdf'));
        $this->assertFalse($this->validator->isExtensionAllowed('php'));
        $this->assertFalse($this->validator->isExtensionAllowed('phtml'));
    }

    /**
     * @test
     */
    public function it_returns_allowed_extensions_list(): void
    {
        $extensions = $this->validator->getAllowedExtensions();

        $this->assertIsArray($extensions);
        $this->assertContains('jpg', $extensions);
        $this->assertContains('pdf', $extensions);
        $this->assertNotContains('php', $extensions);
    }

    /**
     * @test
     */
    public function it_returns_allowed_mime_types_list(): void
    {
        $mimeTypes = $this->validator->getAllowedMimeTypes();

        $this->assertIsArray($mimeTypes);
        $this->assertContains('image/jpeg', $mimeTypes);
        $this->assertContains('application/pdf', $mimeTypes);
    }

    /**
     * Helper: Create a mock UploadedFile
     */
    private function createMockUploadedFile(string $filename, string $mimeType): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tempFile, 'test content');

        return new UploadedFile(
            $tempFile,
            $filename,
            $mimeType,
            null,
            true // test mode
        );
    }

    /**
     * Helper: Create a real image file for testing
     */
    private function createRealImageFile(string $filename): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'image_test_');

        // Create a minimal valid JPEG
        // JPEG signature: FF D8 FF E0 + JFIF header
        $jpegData = hex2bin('FFD8FFE000104A46494600010100000100010000');

        // Add some dummy image data
        $jpegData .= str_repeat("\x00", 100);

        // Add JPEG end marker: FF D9
        $jpegData .= hex2bin('FFD9');

        file_put_contents($tempFile, $jpegData);

        return $tempFile;
    }

    /**
     * Helper: Create a real PDF file for testing
     */
    private function createRealPdfFile(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');

        // Minimal valid PDF
        $pdfContent = "%PDF-1.4\n";
        $pdfContent .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
        $pdfContent .= "2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n";
        $pdfContent .= "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n";
        $pdfContent .= "xref\n0 4\n";
        $pdfContent .= "0000000000 65535 f\n";
        $pdfContent .= "0000000009 00000 n\n";
        $pdfContent .= "0000000056 00000 n\n";
        $pdfContent .= "0000000111 00000 n\n";
        $pdfContent .= "trailer<</Size 4/Root 1 0 R>>\n";
        $pdfContent .= "startxref\n190\n%%EOF";

        file_put_contents($tempFile, $pdfContent);

        return $tempFile;
    }
}
