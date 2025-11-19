<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\Mime\MimeTypesInterface;

class MediaDataLoader implements LoaderInterface
{
    public function __construct(
        private FilesystemOperator $filesystem,
        protected MimeTypesInterface $extensionGuesser,
        protected LoaderInterface $defaultLoader,
    ) {
    }

    public function find(mixed $path): BinaryInterface|string
    {
        try {
            assert(is_string($path), '$path must be a string');

            $mimeType = $this->filesystem->mimeType($path);

            assert(is_string($mimeType), 'Mime type cannot be found');

            $extension = $this->getExtension($mimeType);

            return new Binary(
                $this->filesystem->read($path),
                $mimeType,
                $extension,
            );
        } catch (FilesystemException $filesystemException) {
            return $this->defaultLoader->find($path);
        }
    }

    private function getExtension(string $mimeType): ?string
    {
        return $this->extensionGuesser->getExtensions($mimeType)[0] ?? null;
    }
}
