<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaBeforeFileCreated;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaFileSaved;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaFileUploaded;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Upload
{
    /**
     * upload new files.
     *
     * @param Request $request [description]
     */
    public function upload(Request $request): JsonResponse
    {
        // CSRF Protection
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        $upload_folder_id = (int) $request->request->get('upload_folder');
        $folder = null;
        $custom_attr = [];
        if ($upload_folder_id > 0) {
            $folder = $this->manager->getFolder($upload_folder_id);
        }

        $random_name = filter_var($request->request->get('random_names'), \FILTER_VALIDATE_BOOLEAN);
        if (is_string($request->request->get('custom_attrs', '[]'))) {
            /** @var array<int, array{
             * name: string,
             * options: array<string, mixed>,
             * }> $custom_attr
             **/
            $custom_attr = json_decode($request->request->get('custom_attrs', '[]'), true, 512, \JSON_THROW_ON_ERROR);
        }
        $result = [];

        $one = $request->files->get('file');

        assert($one instanceof UploadedFile || null === $one);

        if ($one && $this->allowUpload($one)) {
            try {
                $one = $this->optimizeUpload($one);
                $orig_name = $one->getClientOriginalName();
                $name = $random_name ? $this->helper->getRandomString() : null;

                if ($request->request->get('dzuuid')) {
                    $chunksRes = self::resumableUpload($request, $one->getRealPath(), $orig_name, $this->chunksDir);

                    if (!$chunksRes['final']) {
                        return new JsonResponse($chunksRes);
                    }

                    if (is_string($chunksRes['path'])) {
                        $one = new File($chunksRes['path']);
                    }
                }

                if (!empty($custom_attr)) {
                    $custom_attr = array_filter($custom_attr, static fn ($entry) => $entry['name'] === $orig_name);
                    /** @var array{
                     * name: string,
                     * options: array<string, mixed>,
                     * } $custom_attr
                     **/
                    $custom_attr = current($custom_attr);
                }

                $file_options = empty($custom_attr) ? [] : $custom_attr['options'];
                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new MediaBeforeFileCreated($one, $folder ? $folder->getPath() : null, $name), MediaBeforeFileCreated::NAME);
                $one = $beforeFileCreatedEvent->getData();
                $folderPath = $beforeFileCreatedEvent->getFolderPath();
                $name = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($one, $folderPath, $name);
                if ($one instanceof File) {
                    $filesystem = new Filesystem();
                    $filesystem->remove(Path::normalize($one->getRealPath()));
                }

                $media->setMetas(array_merge($media->getMetas(), $file_options));
                $this->manager->save($media);
                $this->eventDispatcher->dispatch(new MediaFileUploaded($media->getPath(), $media->getMime(), $media->getMetas()), MediaFileUploaded::NAME);
                $result[] = [
                    'success' => true,
                    'file_name' => $media->getName(),
                ];
            } catch (\Exception $exception) {
                $result[] = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            $result[] = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'SyliusHappyCMSPlugin'),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * save cropped image.
     *
     * @param Request $request [description]
     */
    public function uploadEditedImage(Request $request): JsonResponse
    {
        // CSRF Protection
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        if ($this->allowUpload()) {
            /** @var array{
             *     folder: int|null,
             *     name: string,
             *     data: string,
             *     } $data */
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            $upload_folder_id = $data['folder'];
            $folder = null;
            if (!empty($upload_folder_id)) {
                $folder = $this->manager->getFolder($upload_folder_id);
            }

            $upload_path = $folder?->getPath();
            $original = $data['name'];
            $name_only = pathinfo((string) $original, \PATHINFO_FILENAME) . '_' . $this->helper->getRandomString();

            try {
                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new MediaBeforeFileCreated($data['data'], $upload_path, $name_only), MediaBeforeFileCreated::NAME);
                $data['data'] = $beforeFileCreatedEvent->getData();
                $upload_path = $beforeFileCreatedEvent->getFolderPath();
                $name_only = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($data['data'], $upload_path, $name_only);
                $this->eventDispatcher->dispatch(new MediaFileSaved($media->getPath(), $media->getMime()), MediaFileSaved::NAME);
                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            } catch (FilesystemException|NotFoundExceptionInterface|ContainerExceptionInterface|\Exception $e) {
                $result = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'SyliusHappyCMSPlugin'),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * save image from link.
     *
     * @param Request $request [description]
     */
    public function uploadLink(Request $request): JsonResponse
    {
        // CSRF Protection
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        if ($this->allowUpload()) {
            /** @var array{
             *     url: string,
             *     folder: int|null,
             *     random_names: bool,
             *     } $data */
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            $url = $data['url'];
            $upload_folder_id = $data['folder'];
            $folder = null;
            if (!empty($upload_folder_id)) {
                $folder = $this->manager->getFolder($upload_folder_id);
            }

            try {
                $random_name = filter_var($data['random_names'], \FILTER_VALIDATE_BOOLEAN);
                $name = $random_name ? $this->helper->getRandomString() : null;

                $beforeFileCreatedEvent = $this->eventDispatcher->dispatch(new MediaBeforeFileCreated($url, $folder ? $folder->getPath() : null, $name), MediaBeforeFileCreated::NAME);
                $url = $beforeFileCreatedEvent->getData();
                $folderPath = $beforeFileCreatedEvent->getFolderPath();
                $name = $beforeFileCreatedEvent->getName();
                $media = $this->manager->createMedia($url, $folderPath, $name);
                $this->eventDispatcher->dispatch(new MediaFileSaved($media->getPath(), $media->getMime()), MediaFileSaved::NAME);

                $result = [
                    'success' => true,
                    'message' => $media->getName(),
                ];
            } catch (FilesystemException|NotFoundExceptionInterface|ContainerExceptionInterface|\Exception $exception) {
                $result = [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => $this->translator->trans('error.cant_upload', [], 'SyliusHappyCMSPlugin'),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * Hook to allow/disallow user upload based on custom business logic
     *
     * SECURITY NOTE:
     * - File type validation is handled in MediaManager using FileValidator
     * - This method is for additional checks like:
     *   - User permissions
     *   - Storage quotas
     *   - Rate limiting
     *   - Custom business rules
     *
     * Override this method to implement custom upload restrictions.
     * DO NOT use this as the only security measure.
     *
     * @param UploadedFile|null $file The file being uploaded (null for clipboard/URL uploads)
     *
     * @return bool True to allow upload, false to deny
     */
    protected function allowUpload(?UploadedFile $file = null): bool
    {
        // Default: allow uploads (file validation is done in MediaManager)
        // Override this method to add custom restrictions

        // Example of custom checks you could add:
        // - Check user storage quota
        // - Verify user permissions
        // - Implement rate limiting
        // - Check file size limits

        return true;
    }

    /**
     * do something to file b4 its saved to the server.
     *
     * @return UploadedFile $file
     */
    protected function optimizeUpload(UploadedFile $file): UploadedFile
    {
        return $file;
    }

    /**
     * @return array{
     *     path: string|false,
     *     final: bool,
     *     successes: string[],
     *     errors: string[],
     *     warnings: string[],
     * }
     */
    private static function resumableUpload(Request $request, string $tmpFilePath, string $filename, string $chunksDir): array
    {
        /** @var string[] $successes */
        $successes = [];
        /** @var string[] $errors */
        $errors = [];
        /** @var string[] $warnings */
        $warnings = [];

        $dzuuid = $request->get('dzuuid', '');
        assert(is_string($dzuuid));

        $identifier = trim($dzuuid);
        $fileChunksFolder = sprintf('%s/%s', $chunksDir, $identifier);
        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::normalize($fileChunksFolder));

        $filename = str_replace([' ', '(', ')'], '_', $filename); // remove problematic symbols
        $info = pathinfo($filename);
        $extension = isset($info['extension']) ? '.' . strtolower($info['extension']) : '';
        $filename = $info['filename'];

        /** @var int|string $totalSize */
        $totalSize = $request->get('dztotalfilesize', 0);
        if (!is_int($totalSize)) {
            $totalSize = (int) $totalSize;
        }

        /** @var int|string $totalChunks */
        $totalChunks = $request->get('dztotalchunkcount', 0);
        if (!is_int($totalChunks)) {
            $totalChunks = (int) $totalChunks;
        }

        /** @var int|string $chunkInd */
        $chunkInd = $request->get('dzchunkindex', 0);
        if (!is_int($chunkInd)) {
            $chunkInd = (int) $chunkInd;
        }

        //$chunkSize = $request->get('dzchunksize', 0);
        //$startByte = $request->get('dzchunkbyteoffset', 0);

        $chunkFile = sprintf('%s/%s.part%d', $fileChunksFolder, $filename, $chunkInd);

        if (!move_uploaded_file($tmpFilePath, $chunkFile)) {
            $errors[] = sprintf('Move error, filename %s, index %s', $filename, $chunkInd);
        }

        if (is_array($errors) && count($errors) == 0 &&
            $newPath = self::checkAllParts(
                $fileChunksFolder,
                $filename,
                $extension,
                $totalSize,
                $totalChunks,
                $chunksDir,
                $successes,
                $errors,
                $warnings,
            )
        ) {
            return [
                'final' => true,
                'path' => $newPath,
                'successes' => $successes,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return [
            'final' => false,
            'path' => false,
            'successes' => $successes,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param string[] $successes
     * @param string[] $errors
     * @param string[] $warnings
     */
    private static function checkAllParts(
        string $fileChunksFolder,
        string $filename,
        string $extension,
        int $totalSize,
        int $totalChunks,
        string $chunksDir,
        array &$successes,
        array &$errors,
        array &$warnings,
    ): string|false {
        $parts = glob(Path::normalize(sprintf('%s/*', $fileChunksFolder)));
        if (is_array($parts)) {
            $successes[] = count($parts) . sprintf(' of %d parts done so far in %s', $totalChunks, $fileChunksFolder);
        }
        $filesystem = new Filesystem();

        // check if all the parts present, and create the final destination file
        if (is_array($parts) && count($parts) === (int) $totalChunks) {
            $loaded_size = 0;
            foreach ($parts as $file) {
                $loaded_size += filesize($file);
            }

            if (
                $loaded_size >= $totalSize && [] === $errors && $newPath = self::createFileFromChunks(
                    $fileChunksFolder,
                    $filename,
                    $extension,
                    $totalSize,
                    $totalChunks,
                    $chunksDir,
                    $successes,
                    $errors,
                    $warnings,
                )
            ) {
                $filesystem->remove(Path::normalize($fileChunksFolder));

                return $newPath;
            }
        }

        return false;
    }

    /**
     * @param string[] $successes
     * @param string[] $errors
     * @param string[] $warnings
     */
    private static function createFileFromChunks(string $fileChunksFolder, string $fileName, string $extension, int $totalSize, int $totalChunks, string $chunksDir, array &$successes, array &$errors, array &$warnings): false|string
    {
        $relPath = Path::normalize($chunksDir . '/assembled');
        $filesystem = new Filesystem();
        $filesystem->mkdir($relPath);

        $saveName = self::getNextAvailableFilename($relPath, $fileName, $extension, $errors);

        if (!$saveName) {
            return false;
        }

        $fp = fopen(sprintf('%s/%s%s', $relPath, $saveName, $extension), 'w');
        if (false === $fp) {
            $errors[] = 'cannot create the destination file';

            return false;
        }

        for ($i = 0; $i < $totalChunks; ++$i) {
            $content = file_get_contents(Path::normalize($fileChunksFolder . '/' . $fileName . '.part' . $i));
            if (is_string($content)) {
                fwrite($fp, $content);
            }
        }

        fclose($fp);

        return Path::normalize(sprintf('%s/%s%s', $relPath, $saveName, $extension));
    }

    /**
     * @param string[] $errors
     */
    private static function getNextAvailableFilename(string $relPath, string $origFileName, string $extension, array &$errors): bool|string
    {
        if (file_exists(Path::normalize(sprintf('%s/%s%s', $relPath, $origFileName, $extension)))) {
            $i = 0;
            while (file_exists(Path::normalize(sprintf('%s/%s_', $relPath, $origFileName) . (++$i) . $extension)) && $i < 10000) {
            }

            /** @phpstan-ignore-next-line */
            if ($i >= 10000) {
                $errors[] = sprintf('Can not create unique name for saving file %s%s', $origFileName, $extension);

                return false;
            }

            return $origFileName . '_' . $i;
        }

        return $origFileName;
    }
}
