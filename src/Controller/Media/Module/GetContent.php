<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Doctrine\Common\Collections\ArrayCollection;
use League\Flysystem\FilesystemException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait GetContent
{
    /**
     * get files in path.
     */
    public function getFiles(Request $request): JsonResponse
    {
        // OPTIONAL: CSRF Protection for read operations
        // Note: This is NOT recommended as it can cause UX issues
        // Uncomment only if you want very strict CSRF protection
        /*
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ]);
        }
        */

        /**
         * @var array{
         *     folder: int|null,
         *     path: string|null,
         *     search: string|null
         * } $data
         */
        $data = $this->readRequestData($request);
        $folder = null;
        $path = '/';
        if (!empty($data['folder'])) {
            $folder = $this->manager->getFolder((int) $data['folder']);
            if ($folder instanceof FolderInterface) {
                $path = $folder->getPath();
            } else {
                return new JsonResponse([
                    'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
                ]);
            }
        }
        if (empty($data['folder']) && !empty($data['path'])) {
            try {
                $folder = $this->manager->folderByPath($data['path']);
                if ($folder instanceof FolderInterface) {
                    $path = $folder->getPath();
                } else {
                    return new JsonResponse([
                        'error' => $this->translator->trans('MediaManager::messages.error.doesnt_exist', ['attr' => $path]),
                    ]);
                }
            } catch (FilesystemException $e) {
                return new JsonResponse([
                    'error' => $this->translator->trans($e->getMessage(), ['attr' => $path]),
                ]);
            }
        }

        $items = $this->paginate($this->getData($folder, $data['search'] ?? null), $this->paginationAmount);

        return new JsonResponse([
            'files' => [
                'path' => $path,
                'breadcrumb' => $this->getBreadcrumb($folder),
                'items' => $items,
            ],
        ]);
    }

    /**
     * rename item.
     */
    public function getItemInfos(Request $request): JsonResponse
    {
        /**
         * @var array{
         *     item?: int|string
         * } $data
         */
        $data = $this->readRequestData($request);
        $mediaId = $data['item'] ?? null;
        if (null !== $mediaId) {
            $mediaRepository = $this->helper->getEntityRepository();
            if ($mediaRepository) {
                /** @var MediaInterface|null $media */
                $media = $mediaRepository->findOneBy(['id' => $mediaId]);
                if ($media) {
                    $path = $media->getPath();
                    $time = $media->getLastModified() ?? null;
                    $metas = $media->getMetas();

                    try {
                        $item = [
                            'id' => $media->getId(),
                            'name' => $media->getName(),
                            'type' => $media->getMime(),
                            'size' => $media->getSize(),
                            'path' => $this->manager->publicUrl($media),
                            'download_url' => $this->manager->downloadUrl($media),
                            'storage_path' => $path,
                            'last_modified' => $time,
                            'last_modified_formated' => $this->helper->getItemTime($time),
                            'metas' => $metas,
                        ];
                    } catch (ContainerExceptionInterface|NotFoundExceptionInterface|\Exception $e) {
                        return new JsonResponse([
                            'error' => $e->getMessage(),
                        ]);
                    }

                    return new JsonResponse($item);
                }
            }
        }

        return new JsonResponse([
            'error' => $this->translator->trans('error.doesnt_exist', ['attr' => $mediaId], 'SyliusHappyCMSPlugin'),
        ]);
    }

    /**
     * Parameters of a read action: the query string of a GET request, the JSON body of a POST request.
     *
     * @return array<string, mixed>
     */
    protected function readRequestData(Request $request): array
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            return $request->query->all();
        }

        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * get files list.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getData(FolderInterface|null $dir, ?string $search = null): array
    {
        $list = [];
        $dirList = $this->getFolderContent($dir, false, $search);
        $storageFolders = array_filter($this->getFolderListByType($dirList, 'dir'), [$this, 'ignoreFiles']);
        $storageFiles = array_filter($this->getFolderListByType($dirList, 'file'), [$this, 'ignoreFiles']);

        // folders
        /** @var FolderInterface $folder */
        foreach ($storageFolders as $folder) {
            $path = $folder->getPath();
            $list[] = [
                'id' => $folder->getId(),
                'name' => $folder->getName(),
                'type' => 'folder',
                'path' => $folder->getPath(),
                'storage_path' => $path,
            ];
        }

        // files
        /** @var MediaInterface $file */
        foreach ($storageFiles as $file) {
            $path = $file->getPath();
            $time = $file->getLastModified() ?? null;
            $metas = $file->getMetas();

            $list[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'type' => $file->getMime(),
                'size' => $file->getSize(),
                'path' => $this->manager->publicUrl($file),
                'download_url' => $this->manager->downloadUrl($file),
                'storage_path' => $path,
                'last_modified' => $time,
                'last_modified_formated' => $this->helper->getItemTime($time),
                'metas' => $metas,
            ];
        }

        return $list;
    }

    /**
     * get directory data.
     *
     * @return array<MediaInterface|FolderInterface>
     */
    protected function getFolderContent(int|string|FolderInterface|null $folder = null, bool $rec = false, ?string $search
    = null): array
    {
        if (is_int($folder)) {
            $folder = $this->manager->getFolder($folder);
        }

        $folderRepository = $this->helper->getFolderRepository();

        if (!$folderRepository) {
            throw new \RuntimeException('Folder Repository not found');
        }

        $mediaRepository = $this->helper->getEntityRepository();

        if (!$mediaRepository) {
            throw new \RuntimeException('Media Repository not found');
        }

        $folderQuery = $folderRepository->createQueryBuilder('f');
        $mediaQuery = $mediaRepository->createQueryBuilder('m');

        if ($folder === null) {
            $folderQuery->andWhere('f.parent IS NULL');
            $mediaQuery->andWhere('m.folder IS NULL');
        } else {
            $folderQuery->andWhere('f.parent = :folder')->setParameter('folder', $folder);
            $mediaQuery->andWhere('m.folder = :folder')->setParameter('folder', $folder);
        }

        if (!empty($search)) {
            if (!$rec) {
                $folderQuery->andWhere('f.name LIKE :search')->setParameter('search', '%' . trim($search) . '%');
            }
            $mediaQuery->andWhere('m.name LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        /** @var array<FolderInterface> $folders */
        $folders = $folderQuery->getQuery()->getResult();
        /** @var array<MediaInterface> $medias */
        $medias = $mediaQuery->getQuery()->getResult();

        $results = array_merge($folders, $medias);
        if ($rec) {
            foreach ($folders as $f) {
                $results = array_merge($results, $this->getFolderContent($f, $rec, $search));
            }
        }

        if ($rec) {
            $results = array_filter($results, static function ($item) {
                return $item instanceof MediaInterface;
            });
        }

        return $results;
    }

    protected function ignoreFiles(MediaInterface|FolderInterface $item): bool
    {
        return !preg_grep($this->ignoreFiles, [$item->getPath()]);
    }

    /**
     * filter directory data by type.
     *
     * @param array<MediaInterface|FolderInterface> $list
     *
     * @return array<MediaInterface|FolderInterface>
     */
    protected function getFolderListByType(array $list, string $type): array
    {
        $list = (new ArrayCollection($list))->filter(static function (MediaInterface|FolderInterface $item) use ($type) {
            if ('dir' === $type) {
                return $item instanceof FolderInterface;
            }

            if ('file' === $type) {
                return $item instanceof MediaInterface;
            }

            return false;
        });

        return $list->toArray();
    }

    /**
     * Folders from the root to the given folder, empty at the root.
     *
     * @return list<array{id: int|null, name: string|null}>
     */
    protected function getBreadcrumb(?FolderInterface $folder): array
    {
        $breadcrumb = [];
        while ($folder instanceof FolderInterface) {
            array_unshift($breadcrumb, ['id' => $folder->getId(), 'name' => $folder->getName()]);
            $folder = $folder->getParent();
        }

        return $breadcrumb;
    }
}
