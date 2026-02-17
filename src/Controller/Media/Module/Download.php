<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

trait Download
{
    /**
     * zip folder.
     *
     * @throws FilesystemException
     */
    public function downloadFolder(Request $request): StreamedResponse
    {
        $name = $request->request->get('name');
        $folders = $request->request->get('folders');

        /** @var DirectoryListing<StorageAttributes> $allPaths */
        $allPaths = $this->filesystem->listContents(sprintf('%s/%s', $folders, $name))
            ->filter(static fn (StorageAttributes $attributes) => $attributes->isFile())
            ->map(static fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        if (is_string($name)) {
            return $this->zipAndDownloadDir(
                $name,
                $allPaths,
            );
        }

        exit;
    }

    /**
     * zip files.
     */
    public function downloadFiles(Request $request): StreamedResponse
    {
        $data = $request->request->get('list', '[]');
        if (is_string($data)) {
            /** @var array<int, array{name: string, storage_path: string}> $list */
            $list = json_decode($data, true, 512, \JSON_THROW_ON_ERROR);
        }
        $name = $request->request->get('name');

        return $this->zipAndDownload(
            $name . '-files',
            $list ?? [],
        );
    }

    /**
     * zip ops.
     *
     * @param array<int, array{name: string, storage_path: string}> $list
     */
    protected function zipAndDownload(string $name, array $list): StreamedResponse
    {
        return new StreamedResponse(function () use ($name, $list): void {
            $zipOption = new Archive();
            $zipOption->setDeflateLevel(9);
            $zipOption->setSendHttpHeaders(true);
            $zipOption->setContentType('application/octet-stream');
            $zip = new ZipStream(
                sprintf('%s.zip', $name),
                $zipOption,
            );

            foreach ($list as $file) {
                $name = $file['name'];
                $path = $file['storage_path'];
                $streamRead = $this->filesystem->readStream($path);
                $zip->addFileFromStream($name, $streamRead);
            }

            $zip->finish();
        });
    }

    /**
     * zip dir ops.
     *
     * @param DirectoryListing<StorageAttributes> $list
     */
    protected function zipAndDownloadDir(string $name, DirectoryListing $list): StreamedResponse
    {
        return new StreamedResponse(function () use ($name, $list): void {
            $zipOption = new Archive();
            $zipOption->setDeflateLevel(9);
            $zipOption->setSendHttpHeaders(true);
            $zipOption->setContentType('application/octet-stream');
            $zip = new ZipStream(
                sprintf('%s.zip', $name),
                $zipOption,
            );

            foreach ($list->toArray() as $file) {
                $path = $file->path();
                $dir_name = pathinfo($path, \PATHINFO_DIRNAME);
                $file_name = pathinfo($path, \PATHINFO_BASENAME);
                $full_name = sprintf('%s/%s', $dir_name, $file_name);
                $streamRead = $this->filesystem->readStream($path);
                $zip->addFileFromStream($full_name, $streamRead);
            }

            $zip->finish();
        });
    }

    /**
     * @throws NotLoadableException
     */
    public function downloadFile(string $path): StreamedResponse
    {
        // SECURITY: Sanitize and validate the path against database
        $sanitizedPath = $this->helper->sanitizePath($path);

        // Verify that this path corresponds to a real media in the database
        $media = $this->helper->getMediaByPath($sanitizedPath);

        if (!$media) {
            throw new NotLoadableException(sprintf('Media not found.'));
        }

        // Use the validated path from the media entity
        $validatedPath = $media->getPath();

        try {
            $mimeType = $this->filesystem->mimeType($validatedPath);

            $stream = $this->filesystem->readStream($validatedPath);
            $response = new StreamedResponse(static function () use ($stream) {
                fpassthru($stream);
                exit;
            });

            $response->setLastModified((new \DateTime())->setTimestamp($this->filesystem->lastModified($validatedPath)));
            $response->headers->set('Content-Type', $mimeType);
            $response->setPublic();
            $response->setMaxAge(60 * 12);
            $response->setSharedMaxAge(60 * 12);

            return $response;
        } catch (FilesystemException $filesystemException) {
            throw new NotLoadableException('File cannot be loaded.', 0, $filesystemException);
        }
    }
}
