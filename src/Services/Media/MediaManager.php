<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaBeforeSetMetas;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\AlreadyExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\ExtNotAllowed;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\FolderAlreadyExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\FolderNotExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\NoFile;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\ProviderNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaManager
{
    public function __construct(
        protected FilesystemOperator $filesystem,
        protected MediaHelper $helper,
        public EntityManagerInterface $em,
        protected ContainerBagInterface $parameters,
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $eventDispatcher,
        protected FileValidator $fileValidator,
        protected UrlValidator $urlValidator,
    ) {
    }

    public function getFilesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getHelper(): MediaHelper
    {
        return $this->helper;
    }

    public function getPath(MediaInterface $media): ?string
    {
        return $this->getHelper()->getPath($media);
    }

    public function publicUrl(MediaInterface $media): string|null
    {
        if ($mediaPath = $this->getPath($media)) {
            try {
                if (method_exists($this->getFilesystem(), 'publicUrl')) {
                    $publicUrl = $this->getFilesystem()->publicUrl($mediaPath);
                } else {
                    $publicUrl = $this->helper->getBaseUrl() . \DIRECTORY_SEPARATOR . $mediaPath;
                }
                if (false !== strpos($this->helper->getBaseUrl(), '://')) {
                    $baseUrlPath = parse_url($this->helper->getBaseUrl(), \PHP_URL_PATH) ?? '';
                    $baseUrl = '/';
                    if ($baseUrlPath) {
                        $baseUrl = str_replace($baseUrlPath, '', $this->helper->getBaseUrl());
                    }
                    $filePath = parse_url($publicUrl, \PHP_URL_PATH);
                    if (is_string($baseUrlPath) && is_string($filePath)) {
                        $path = array_filter(explode('/', $baseUrlPath) + explode('/', $filePath));
                        $publicUrl = $this->helper->clearDblSlash(sprintf('%s/%s', $baseUrl, implode('/', $path)));
                    }
                }

                return $publicUrl;
            } catch (\Exception $exception) {
                return $this->helper->clearDblSlash(sprintf('%s/%s', $this->helper->getBaseUrl(), $mediaPath));
            }
        }

        return null;
    }

    public function downloadUrl(MediaInterface $media): string|null
    {
        return $this->publicUrl($media);
    }

    public function getFolder(int|string $id): ?FolderInterface
    {
        $folder = null;
        $folderRepository = $this->getHelper()->getFolderRepository();
        if ($folderRepository) {
            /** @var ?FolderInterface $folder */
            $folder = $folderRepository->find($id);
        }

        return $folder;
    }

    public function getMedia(int|string|MediaInterface $media): ?MediaInterface
    {
        return $this->getHelper()->getMedia($media);
    }

    public function directoryExists(string $path): bool
    {
        return is_dir($this->parameters->get('kernel.project_dir') . \DIRECTORY_SEPARATOR . 'public' . $this->helper->getBaseUrl() . \DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @throws FilesystemException
     */
    public function folderByPath(?string $path): FolderInterface|null|false
    {
        if (null === $path || $this->directoryExists($path)) {
            $slugs = array_values(array_filter(explode('/', (string) $path)));
            $parent = null;
            foreach ($slugs as $i => $slug) {
                $folder = null;
                $folderRepository = $this->getHelper()->getFolderRepository();
                if ($folderRepository) {
                    /** @var FolderInterface $folder */
                    $folder = $folderRepository->findOneBy([
                       'parent' => $parent,
                       'slug' => $slug,
                    ]);
                }
                if (
                    ($folder) !== null
                ) {
                    $parent = $folder;
                }

                if ($i === count($slugs) - 1) {
                    return $folder ?: false;
                }
            }

            return $parent;
        }

        return false;
    }

    /**
     * @throws FilesystemException|FolderNotExist|FolderAlreadyExist
     */
    public function createFolder(?string $name, ?string $path = null): ?FolderInterface
    {
        if ('.' === $path) {
            $path = '';
        }

        $class = $this->getHelper()->getFolderClassName();
        /** @var FolderInterface $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($name);
        }

        $folderCreation = false;
        $folder = $this->folderByPath($path);
        if (false === $folder && !empty($path)) {
            $folder = $this->createFolder(basename($path), dirname($path));
            $folderCreation = true;
        }

        $folderRepository = $this->getHelper()->getFolderRepository();

        if (!$folderCreation && $folderRepository) {
            $existFolder = $folderRepository->findOneBy(['parent' => $folder ?: null, 'name' => $name]);
            if ($existFolder instanceof FolderInterface) {
                return $existFolder;
            }
        }

        $entity->setParent($folder ?: null);

        // Verify if media root folder presence, otherwise create it
        if (!$this->filesystem->fileExists('./')) {
            $this->filesystem->createDirectory('./');
        }

        if (!$this->directoryExists($entity->getPath())) {
            $this->filesystem->createDirectory($entity->getPath(), []);
        }

        $this->save($entity);

        return $entity;
    }

    /**
     * @throws AlreadyExist
     * @throws FolderAlreadyExist
     * @throws ContainerExceptionInterface
     * @throws ExtNotAllowed
     * @throws FilesystemException
     * @throws FolderNotExist
     * @throws NoFile
     * @throws NotFoundExceptionInterface
     * @throws ProviderNotFound
     */
    public function createMedia(string|File $source, ?string $path = null, ?string $name = null): MediaInterface
    {
        $class = $this->getHelper()->getMediaClassName();
        /** @var MediaInterface $entity */
        $entity = new $class();

        if ($name) {
            $entity->setName($this->helper->cleanName($name));
        }

        $folder = $this->folderByPath($path);
        if (false === $folder && is_string($path)) {
            $folder = $this->createFolder(basename($path), dirname($path));
        }

        $entity->setFolder($folder ?: null);

        if (is_string($source) && str_starts_with($source, 'data:')) {
            $entity = $this->createFromBase64($entity, $source);
        } elseif (is_string($source) && false !== filter_var($source, \FILTER_VALIDATE_URL)) {
            if ($imageType = @exif_imagetype($source)) {
                $entity = $this->createFromImageURL($entity, $source, $imageType);
            } else {
                $entity = $this->createFromOembed($entity, $source);
            }
        } else {
            $entity = $this->createFromFile($entity, $source);
        }

        $this->save($entity);

        return $entity;
    }

    /**
     * @throws FilesystemException
     */
    public function delete(MediaInterface|FolderInterface $item, ?bool $flush = true): void
    {
        $this->em->remove($item);

        if ($item instanceof FolderInterface) {
            $this->filesystem->deleteDirectory($item->getPath());
        }

        if ($item instanceof MediaInterface) {
            $this->filesystem->delete($item->getPath());
        }

        if ($flush) {
            $this->em->flush();
        }
    }

    public function save(MediaInterface|FolderInterface $item, ?bool $flush = true): void
    {
        $this->em->persist($item);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * @throws FilesystemException
     */
    public function move(string $oldPath, string $newPath): void
    {
        $source = $this->helper->clearDblSlash($oldPath);
        $destination = $this->helper->clearDblSlash($newPath);
        if ($this->getFilesystem()->fileExists($source) || $this->directoryExists($source)) {
            $this->getFilesystem()->move($source, $destination);
        }
    }

    private function createFromOembed(MediaInterface $entity, string $source): MediaInterface
    {
        // SECURITY: Validate URL to prevent SSRF attacks
        try {
            $this->urlValidator->validate($source);
        } catch (\InvalidArgumentException $e) {
            throw new ProviderNotFound(
                sprintf(
                    'URL validation failed for security reasons: %s',
                    $e->getMessage(),
                ),
            );
        }

        $embed = new Embed();
        $infos = $embed->get($source);

        /** @phpstan-ignore-next-line */
        if (($oembed = $infos->getOEmbed()) && !empty($infos->getOEmbed()->all())) {
            $name = $entity->getName() ?: $oembed->get('title');
            $entity->setName($name);
            $entity->setMime('application/json+oembed');
            $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new MediaBeforeSetMetas($entity, $source, [
                'provider' => [
                    'name' => $infos->providerName,
                    'url' => (string) $infos->providerUrl,
                ],
                'author' => [
                    'name' => $infos->authorName,
                    'url' => (string) $infos->authorUrl,
                ],
                'title' => (string) $infos->title,
                'url' => (string) $infos->url,
                'image' => (string) $infos->image,
                'icon' => (string) ($infos->icon ?: $infos->favicon),
                'type' => $oembed->get('type'),
                'code' => [
                    'html' => $infos->code?->html,
                    'width' => $infos->code?->width,
                    'height' => $infos->code?->height,
                    'ratio' => $infos->code?->ratio,
                ],
            ]), MediaBeforeSetMetas::NAME);
            $entity->setMetas($beforeSetMetasEvent->getMetas());
        } else {
            throw new ProviderNotFound($this->translator->trans('error.provider_not_found', [], 'SyliusHappyCMSPlugin'));
        }

        return $entity;
    }

    /**
     * @throws AlreadyExist
     * @throws FilesystemException
     * @throws NoFile
     */
    private function createFromBase64(MediaInterface $entity, string $source): MediaInterface
    {
        if (preg_match('#^data\:([a-zA-Z]+\/[a-zA-Z]+);base64\,([a-zA-Z0-9\+\/]+\=*)$#', (string) $source, $matches)) {
            $infos = [
                'mime' => $matches[1],
                'data' => base64_decode((string) $matches[2]),
            ];
        } else {
            throw new NoFile($this->translator->trans('error.no_file', [], 'SyliusHappyCMSPlugin'));
        }

        $entity->setName($this->helper->cleanName(''));
        $filename = strtolower((new AsciiSlugger())->slug(strtolower((string) $entity->getName()))->toString() . '.' . MediaHelper::mime2ext($infos['mime']));
        $entity->setSlug($filename);

        $mediaRepository = $this->getHelper()->getMediaRepository();

        if ($mediaRepository && !empty($mediaRepository->findBy([
            'folder' => $entity->getFolder(),
            'name' => $entity->getName()]))
        ) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'SyliusHappyCMSPlugin'));
        }

        if (!$this->filesystem->fileExists($entity->getPath())) {
            $this->filesystem->write($entity->getPath(), $infos['data']);
        }

        $entity->setSize($this->filesystem->fileSize($entity->getPath()));
        $entity->setLastModified($this->filesystem->lastModified($entity->getPath()));
        $entity->setMime($this->filesystem->mimeType($entity->getPath()));

        if ($entity->getMime() && str_contains($entity->getMime(), 'image/')) {
            $tmp = tmpfile();
            if (false !== $tmp) {
                fwrite($tmp, $this->filesystem->read($entity->getPath()));
                $meta = stream_get_meta_data($tmp);
                /** @phpstan-ignore-next-line */
                $this->setImageMetas($entity, $meta['uri'], $source);
            }
        }

        return $entity;
    }

    private function setImageMetas(MediaInterface &$entity, string $path, null | string | File $source): void
    {
        $imageSize = getimagesize($path);
        if (is_array($imageSize)) {
            [$width, $height] = $imageSize;
            $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new MediaBeforeSetMetas($entity, $source, [
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $height / $width * 100,
                ],
            ]), MediaBeforeSetMetas::NAME);
            $entity->setMetas($beforeSetMetasEvent->getMetas());
        }
    }

    /**
     * @throws ExtNotAllowed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AlreadyExist
     * @throws FilesystemException
     * @throws NoFile
     */
    private function createFromImageURL(MediaInterface $entity, string $source, int $type): MediaInterface
    {
        // SECURITY: Validate URL to prevent SSRF attacks
        try {
            $this->urlValidator->validate($source);
        } catch (\InvalidArgumentException $e) {
            throw new NoFile(
                sprintf(
                    'URL validation failed for security reasons: %s',
                    $e->getMessage(),
                ),
            );
        }

        $urlPath = parse_url($source, \PHP_URL_PATH);
        $original = substr((string) $urlPath, strrpos((string) $urlPath, '/') + 1);
        $name = $entity->getName() ?: pathinfo($original, \PATHINFO_FILENAME);

        $file_type = image_type_to_mime_type($type);
        $ext_only = MediaHelper::mime2ext($file_type) ?: pathinfo($original, \PATHINFO_EXTENSION);

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower((string) $name))->toString() . sprintf('.%s', $ext_only));
        $entity->setSlug($final_name_slug);

        if (empty($entity->getName())) {
            $entity->setName($name);
        }

        /** @var array|null $unAllowedMimes */
        $unAllowedMimes = $this->parameters->get('sylius_happy_cms.media.unallowed_mimes');

        $ignore = array_merge($unAllowedMimes ?? [], ['application/octet-stream']);

        // check for mime type
        if (is_string($file_type) && Str::contains($file_type, $ignore)) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'SyliusHappyCMSPlugin'));
        }

        $mediaRepository = $this->getHelper()->getMediaRepository();

        if ($mediaRepository && !empty($mediaRepository->findBy([
            'folder' => $entity->getFolder(),
            'name' => $entity->getName(),
            ]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'SyliusHappyCMSPlugin'));
        }

        try {
            $filepath = $entity->getPath();
            if (!$this->filesystem->fileExists($filepath)) {
                // SECURITY: Double-check URL before fetching
                // This prevents race conditions where URL could be modified between validation and fetch
                $this->urlValidator->validate($source);
                $stream = file_get_contents($source);
                if ($stream) {
                    $this->filesystem->write($filepath, $stream);
                }
            }

            $fileSize = getimagesize($source);
            if (is_array($fileSize)) {
                [$width, $height] = $fileSize;
                $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new MediaBeforeSetMetas($entity, $source, [
                    'dimensions' => [
                        'width' => $width,
                        'height' => $height,
                        'ratio' => $height / $width * 100,
                    ],
                ]), MediaBeforeSetMetas::NAME);
                $entity->setMetas($beforeSetMetasEvent->getMetas());
            }

            $entity->setSize($this->filesystem->fileSize($entity->getPath()));
            $entity->setLastModified($this->filesystem->lastModified($entity->getPath()));
            $entity->setMime($this->filesystem->mimeType($entity->getPath()));
        } catch (\Throwable) {
            throw new NoFile($this->translator->trans('error.no_file', [], 'SyliusHappyCMSPlugin'));
        }

        return $entity;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws AlreadyExist
     * @throws ContainerExceptionInterface
     * @throws FilesystemException
     * @throws NoFile
     * @throws ExtNotAllowed
     */
    private function createFromFile(MediaInterface $entity, string|File|UploadedFile $source): Media
    {
        $datas = [];
        if (is_string($source)) {
            $source = new File($source);
        }

        if (!($source instanceof File)) {
            throw new NoFile();
        }

        if ($source instanceof UploadedFile) {
            // SECURITY: Validate uploaded file BEFORE processing
            try {
                $this->fileValidator->validate($source);
            } catch (\InvalidArgumentException $e) {
                throw new ExtNotAllowed($e->getMessage());
            }

            $orig_name = $source->getClientOriginalName();
            $name = $entity->getName() ?: pathinfo($orig_name, \PATHINFO_FILENAME);
            $ext_only = pathinfo($orig_name, \PATHINFO_EXTENSION);

            // SECURITY: Use real MIME type from file content, NOT client-provided header
            $realMimeType = $this->fileValidator->getRealMimeType($source);
            $entity->setMime($realMimeType);

            if ($ext = MediaHelper::mime2ext($realMimeType)) {
                $ext_only = $ext;
            }

            if (empty($entity->getName())) {
                $entity->setName($source->getClientOriginalName());
            }
        } else {
            $orig_name = $source->getFilename();
            $name = $entity->getName() ?: $source->getBasename('.' . $source->getExtension());
            $ext_only = pathinfo($orig_name, \PATHINFO_EXTENSION);

            // For non-uploaded files (internal operations), use getMimeType()
            if ($type = $source->getMimeType()) {
                $entity->setMime($type);
                if ($ext = MediaHelper::mime2ext($type)) {
                    $ext_only = $ext;
                }
            }

            if (empty($entity->getName())) {
                $entity->setName($source->getFilename());
            }
        }

        $final_name_slug = strtolower((new AsciiSlugger())->slug(strtolower((string) $name))->toString() . sprintf('.%s', $ext_only));
        $entity->setSlug($final_name_slug);
        $entity->setSize($source->getSize());
        $entity->setLastModified($source->getMTime());

        /** @var array|null $unAllowedMimes */
        $unAllowedMimes = $this->parameters->get('sylius_happy_cms.media.unallowed_mimes');

        /** @var array|null $unAllowedExt */
        $unAllowedExt = $this->parameters->get('sylius_happy_cms.media.unallowed_ext');

        // check for mime type
        if (is_array($unAllowedMimes) && is_string($entity->getMime()) && Str::contains($entity->getMime(), $unAllowedMimes)) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'SyliusHappyCMSPlugin'));
        }

        // check for extension
        if (is_array($unAllowedExt) && is_string($ext_only) && Str::contains($ext_only, $unAllowedExt)) {
            throw new ExtNotAllowed($this->translator->trans('not_allowed_file_ext', [], 'SyliusHappyCMSPlugin'));
        }

        $mediaRepository = $this->getHelper()->getMediaRepository();

        if ($mediaRepository && !empty($mediaRepository->findBy(['folder' => $entity->getFolder(), 'name' => $entity->getName()]))) {
            throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'SyliusHappyCMSPlugin'));
        }

        if (@exif_imagetype($source->getPathname())) {
            $this->setImageMetas($entity, $source->getPathname(), $source);
        }

        try {
            if ($this->helper->fileIsType($entity->getMime(), 'video') || $this->helper->fileIsType($entity->getMime(), 'audio')) {
                $getID3 = new \getID3();
                $id3Datas = $getID3->analyze($source->getPathname());

                if (isset($id3Datas['video']) && $this->helper->fileIsType($entity->getMime(), 'video')) {
                    $datas = [
                        'duration' => $id3Datas['playtime_seconds'],
                        'frame_rate' => $id3Datas['video']['frame_rate'],
                        'dimensions' => [
                            'width' => $id3Datas['video']['resolution_x'],
                            'height' => $id3Datas['video']['resolution_y'],
                            'ratio' => $id3Datas['video']['resolution_y'] / $id3Datas['video']['resolution_x'] * 100,
                        ],
                    ];
                }

                if (isset($id3Datas['audio']) && $this->helper->fileIsType($entity->getMime(), 'audio')) {
                    $datas = [
                        'duration' => $id3Datas['playtime_seconds'],
                        'tags' => [],
                    ];
                    if (!empty($id3Datas['id3v1']['title'])) {
                        $datas['tags']['title'] = $id3Datas['id3v1']['title'];
                    }

                    if (!empty($id3Datas['id3v1']['artist'])) {
                        $datas['tags']['artist'] = $id3Datas['id3v1']['artist'];
                    }

                    if (!empty($id3Datas['id3v1']['album'])) {
                        $datas['tags']['album'] = $id3Datas['id3v1']['album'];
                    }

                    if (!empty($id3Datas['id3v1']['year'])) {
                        $datas['tags']['year'] = $id3Datas['id3v1']['year'];
                    }
                }

                $beforeSetMetasEvent = $this->eventDispatcher->dispatch(new MediaBeforeSetMetas($entity, $source, $datas), MediaBeforeSetMetas::NAME);
                $entity->setMetas($beforeSetMetasEvent->getMetas());
            }
        } catch (\Exception) {
        }

        // check unexistence
        $filepath = $this->helper->clearDblSlash($entity->getPath());
        if (!$this->filesystem->fileExists($filepath)) {
            // throw new AlreadyExist($this->translator->trans('error.already_exists', [], 'SyliusHappyCMSPlugin'));
            $stream = fopen($source->getRealPath(), 'rb+');
            if ($stream) {
                $this->filesystem->writeStream($entity->getPath(), $stream);
                fclose($stream);
            }
        }

        return $entity;
    }
}
