<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET media.global_search: the folders and media whose name contains `q` (or `search`), in the whole media library
 * or under a folder, paginated like get-files.
 *
 * Every matching id is read first (scalar rows), then filtered on `ignore_files` and the requested types, so that
 * the total and the pages stay exact; only the entities of the requested page are loaded.
 */
trait GlobalSearch
{
    /** Shortest search term: below it, the search returns an empty page. */
    protected int $globalSearchMinLength = 2;

    public function globalSearch(Request $request): JsonResponse
    {
        $query = $request->query->all();

        $type = $query['type'] ?? 'all';
        if (!is_string($type) || !in_array($type, ['all', 'media', 'folder'], true)) {
            return new JsonResponse([
                'error' => 'Unknown type: expected "all", "media" or "folder".',
            ], Response::HTTP_BAD_REQUEST);
        }

        $scopeId = $query['folder'] ?? null;
        if (null !== $scopeId && '' !== $scopeId && (!is_string($scopeId) || !ctype_digit($scopeId))) {
            return new JsonResponse(['error' => 'Invalid folder: expected a folder id.'], Response::HTTP_BAD_REQUEST);
        }
        $scopeId = null === $scopeId || '' === $scopeId ? null : (int) $scopeId;

        $term = $this->globalSearchTerm($query);
        $types = $this->globalSearchTypes($query['types'] ?? null);
        $page = $query['page'] ?? '1';
        $page = is_string($page) && ctype_digit($page) && (int) $page > 0 ? (int) $page : 1;
        $parameters = array_filter([
            'q' => $term,
            'type' => 'all' === $type ? null : $type,
            'folder' => $scopeId,
            'types' => $types,
        ], static fn ($value) => null !== $value);

        if (null === $term || mb_strlen($term) < $this->globalSearchMinLength) {
            return new JsonResponse(['items' => $this->globalSearchPage($request, $parameters, [], 0, $page)]);
        }

        $tree = $this->globalSearchFolderTree($term);
        if (null !== $scopeId && !isset($tree[$scopeId])) {
            return new JsonResponse([
                'error' => $this->translator->trans('error.doesnt_exist', ['attr' => $scopeId], 'SyliusHappyCMSPlugin'),
            ], Response::HTTP_NOT_FOUND);
        }
        // The folders under the scope folder; its media are searched too.
        $scope = null === $scopeId ? null : $this->globalSearchDescendants($tree, $scopeId);

        /** @var list<array{kind: 'folder'|'media', id: int, folder?: int|null}> $matches */
        $matches = [];
        if ('media' !== $type) {
            foreach ($tree as $id => $folder) {
                if ($folder['matched'] &&
                    (null === $scope || isset($scope[$id])) &&
                    !$this->globalSearchIgnored($folder['path'])) {
                    $matches[] = ['kind' => 'folder', 'id' => $id];
                }
            }
        }

        // As in the media manager script, `types[]=folder` alone keeps the folders only.
        if ('folder' !== $type && ['folder'] !== $types) {
            if (null !== $scope && null !== $scopeId) {
                $scope[$scopeId] = true;
            }
            foreach ($this->globalSearchMediaRows($term) as $row) {
                $folderId = $row['folder'];
                if (null !== $scope && (null === $folderId || !isset($scope[$folderId]))) {
                    continue;
                }
                $path = (null === $folderId ? '' : ($tree[$folderId]['path'] ?? '') . '/') . $row['slug'];
                if ($this->globalSearchIgnored($path) ||
                    (null !== $types && !$this->globalSearchMatchesTypes($row['mime'], $types))) {
                    continue;
                }
                $matches[] = ['kind' => 'media', 'id' => $row['id'], 'folder' => $folderId];
            }
        }

        $perPage = max(1, $this->paginationAmount);
        $pageMatches = array_slice($matches, ($page - 1) * $perPage, $perPage);

        return new JsonResponse([
            'items' => $this->globalSearchPage($request, $parameters, $this->globalSearchItems($pageMatches, $tree), count($matches), $page),
        ]);
    }

    /**
     * `q`, then `search` when `q` is missing or blank.
     *
     * @param array<string, mixed> $query
     */
    private function globalSearchTerm(array $query): ?string
    {
        foreach (['q', 'search'] as $name) {
            $value = $query[$name] ?? null;
            if (is_string($value) && '' !== trim($value)) {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * The `types[]` of the picker restrictions, normalized like the media manager script: `null` for every type.
     *
     * @return list<string>|null
     */
    private function globalSearchTypes(mixed $raw): ?array
    {
        $raw = is_string($raw) ? [$raw] : (is_array($raw) ? $raw : []);

        $types = [];
        foreach ($raw as $type) {
            if (!is_string($type) || '' === $type) {
                continue;
            }
            $type = match ($type) {
                'all' => null,
                'images' => 'image',
                'videos' => 'video',
                'folders' => 'folder',
                'applications' => 'application',
                'archive', 'archives' => 'compressed',
                default => str_contains($type, '/*') ? explode('/', $type)[0] : $type,
            };
            if (null !== $type && !in_array($type, $types, true)) {
                $types[] = $type;
            }
        }

        return [] === $types ? null : $types;
    }

    /**
     * Whether a media of this MIME type belongs to one of the categories (rules of the media manager script).
     *
     * @param list<string> $types
     */
    private function globalSearchMatchesTypes(?string $mime, array $types): bool
    {
        if (null === $mime || '' === $mime) {
            return false;
        }

        $images = $this->extendedMimes['image'] ?? [];
        $archives = $this->extendedMimes['archive'] ?? [];
        foreach ($types as $category) {
            if ('folder' === $category) {
                continue;
            }
            if ('image' === $category && in_array($mime, $images, true)) {
                return true;
            }
            if ((str_contains($mime, 'oembed') && 'oembed' !== $category) ||
                (str_contains($mime, 'pdf') && 'pdf' !== $category)) {
                continue;
            }
            if (str_contains($mime, 'compressed') || in_array($mime, $archives, true)) {
                if ('compressed' === $category) {
                    return true;
                }

                continue;
            }
            if (str_contains($mime, $category)) {
                return true;
            }
        }

        return false;
    }

    private function globalSearchIgnored(string $path): bool
    {
        return '' !== $this->ignoreFiles && [] !== preg_grep($this->ignoreFiles, [$path]);
    }

    /**
     * Every folder (one scalar query), sorted by name, with its path, its parent and whether its name matches.
     *
     * @return array<int, array{name: string, slug: string, parent: int|null, path: string, matched: bool}>
     */
    private function globalSearchFolderTree(string $term): array
    {
        /** @var list<array{id: int|string, name: string, slug: string, parent: int|string|null, matched: int|string}> $rows */
        $rows = $this->manager->em->createQueryBuilder()
            ->select('f.id', 'f.name', 'f.slug', 'IDENTITY(f.parent) AS parent')
            ->addSelect("CASE WHEN f.name LIKE :search ESCAPE '!' THEN 1 ELSE 0 END AS matched")
            ->from($this->globalSearchClass($this->helper->getFolderClassName()), 'f')
            ->orderBy('f.name', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->setParameter('search', $this->globalSearchPattern($term))
            ->getQuery()
            ->getScalarResult();

        $tree = [];
        foreach ($rows as $row) {
            $tree[(int) $row['id']] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
                'parent' => null === $row['parent'] ? null : (int) $row['parent'],
                'path' => '',
                'matched' => 1 === (int) $row['matched'],
            ];
        }
        foreach (array_keys($tree) as $id) {
            $slugs = [];
            foreach ($this->globalSearchAncestors($tree, $id) as $ancestor) {
                $slugs[] = $tree[$ancestor]['slug'];
            }
            $tree[$id]['path'] = implode('/', $slugs);
        }

        return $tree;
    }

    /**
     * The folder ids from the root down to this folder (guarded against a cycle).
     *
     * @param array<int, array{parent: int|null}> $tree
     *
     * @return list<int>
     */
    private function globalSearchAncestors(array $tree, int $id): array
    {
        $ids = [];
        for ($current = $id; null !== $current && isset($tree[$current]) && !in_array($current, $ids, true); $current = $tree[$current]['parent']) {
            $ids[] = $current;
        }

        return array_reverse($ids);
    }

    /**
     * The sub-folders of a folder, at any depth (the folder itself excluded).
     *
     * @param array<int, array{parent: int|null}> $tree
     *
     * @return array<int, true>
     */
    private function globalSearchDescendants(array $tree, int $folderId): array
    {
        $children = [];
        foreach ($tree as $id => $folder) {
            if (null !== $folder['parent']) {
                $children[$folder['parent']][] = $id;
            }
        }

        $descendants = [];
        $queue = $children[$folderId] ?? [];
        while ([] !== $queue) {
            $id = array_shift($queue);
            if (isset($descendants[$id]) || $id === $folderId) {
                continue;
            }
            $descendants[$id] = true;
            array_push($queue, ...($children[$id] ?? []));
        }

        return $descendants;
    }

    /**
     * The media whose name matches (one scalar query), sorted by name.
     *
     * @return list<array{id: int, slug: string, mime: string|null, folder: int|null}>
     */
    private function globalSearchMediaRows(string $term): array
    {
        /** @var list<array{id: int|string, slug: string, mime: string|null, folder: int|string|null}> $rows */
        $rows = $this->manager->em->createQueryBuilder()
            ->select('m.id', 'm.slug', 'm.mime', 'IDENTITY(m.folder) AS folder')
            ->from($this->globalSearchClass($this->helper->getMediaClassName()), 'm')
            ->where("m.name LIKE :search ESCAPE '!'")
            ->orderBy('m.name', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setParameter('search', $this->globalSearchPattern($term))
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'mime' => $row['mime'],
            'folder' => null === $row['folder'] ? null : (int) $row['folder'],
        ], $rows);
    }

    /**
     * @return class-string
     */
    private function globalSearchClass(string $class): string
    {
        if (!class_exists($class)) {
            throw new \LogicException(sprintf('The media manager entity "%s" does not exist.', $class));
        }

        return $class;
    }

    private function globalSearchFolderId(MediaInterface $media): ?int
    {
        $id = $media->getFolder()?->getId();

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * A `LIKE` pattern in which the term is literal (`%` and `_` are not wildcards).
     */
    private function globalSearchPattern(string $term): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
    }

    /**
     * The items of one page, in the listing format plus their folder (`folder_id`, `dir_path`, `breadcrumb`).
     *
     * @param list<array{kind: 'folder'|'media', id: int, folder?: int|null}> $matches
     * @param array<int, array{name: string, slug: string, parent: int|null, path: string, matched: bool}> $tree
     *
     * @return list<array<string, mixed>>
     */
    private function globalSearchItems(array $matches, array $tree): array
    {
        $medias = $this->globalSearchLoadMedias($matches, $tree);

        $items = [];
        foreach ($matches as $match) {
            if ('folder' === $match['kind']) {
                $folder = $tree[$match['id']];
                $items[] = [
                    'id' => $match['id'],
                    'name' => $folder['name'],
                    'type' => 'folder',
                    'path' => $folder['path'],
                    'storage_path' => $folder['path'],
                ] + $this->globalSearchLocation($tree, $folder['parent']);

                continue;
            }

            $media = $medias[$match['id']] ?? null;
            if (null === $media) {
                continue;
            }
            $time = $media->getLastModified();
            $items[] = [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'type' => $media->getMime(),
                'size' => $media->getSize(),
                'path' => $this->manager->publicUrl($media),
                'download_url' => $this->manager->downloadUrl($media),
                'storage_path' => $media->getPath(),
                'last_modified' => $time,
                'last_modified_formated' => $this->helper->getItemTime($time),
                'metas' => $media->getMetas(),
            ] + $this->globalSearchLocation($tree, $this->globalSearchFolderId($media));
        }

        return $items;
    }

    /**
     * The media of the page, after their folders and the ancestors of those folders: building the media paths and
     * URLs then runs no other query.
     *
     * @param list<array{kind: 'folder'|'media', id: int, folder?: int|null}> $matches
     * @param array<int, array{parent: int|null}> $tree
     *
     * @return array<int, MediaInterface>
     */
    private function globalSearchLoadMedias(array $matches, array $tree): array
    {
        $mediaIds = [];
        $folderIds = [];
        foreach ($matches as $match) {
            if ('media' !== $match['kind']) {
                continue;
            }
            $mediaIds[] = $match['id'];
            if (null !== ($match['folder'] ?? null)) {
                foreach ($this->globalSearchAncestors($tree, $match['folder']) as $id) {
                    $folderIds[$id] = true;
                }
            }
        }
        if ([] === $mediaIds) {
            return [];
        }

        if ([] !== $folderIds) {
            $this->manager->em->createQueryBuilder()
                ->select('f')
                ->from($this->globalSearchClass($this->helper->getFolderClassName()), 'f')
                ->where('f.id IN (:ids)')
                ->setParameter('ids', array_keys($folderIds))
                ->getQuery()
                ->getResult();
        }

        /** @var list<MediaInterface> $loaded */
        $loaded = $this->manager->em->createQueryBuilder()
            ->select('m')
            ->from($this->globalSearchClass($this->helper->getMediaClassName()), 'm')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $mediaIds)
            ->getQuery()
            ->getResult();

        $medias = [];
        foreach ($loaded as $media) {
            $medias[(int) $media->getId()] = $media;
        }

        return $medias;
    }

    /**
     * Where an item is: its folder id, the folder path and the breadcrumb from the root down to the folder.
     *
     * @param array<int, array{name: string, parent: int|null, path: string}> $tree
     *
     * @return array{folder_id: int|null, dir_path: string, breadcrumb: list<array{id: int, name: string}>}
     */
    private function globalSearchLocation(array $tree, ?int $folderId): array
    {
        if (null === $folderId || !isset($tree[$folderId])) {
            return ['folder_id' => null, 'dir_path' => '/', 'breadcrumb' => []];
        }

        return [
            'folder_id' => $folderId,
            'dir_path' => $tree[$folderId]['path'],
            'breadcrumb' => array_map(
                static fn (int $id): array => ['id' => $id, 'name' => $tree[$id]['name']],
                $this->globalSearchAncestors($tree, $folderId),
            ),
        ];
    }

    /**
     * A page in the get-files format, with page URLs that keep the search parameters.
     *
     * @param array<string, mixed> $parameters
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function globalSearchPage(Request $request, array $parameters, array $items, int $total, int $page): array
    {
        $perPage = max(1, $this->paginationAmount);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $path = $request->getBaseUrl() . $request->getPathInfo();
        $url = static fn (int $page): string => $path . '?' . http_build_query($parameters + ['page' => $page]);

        return [
            'current_page' => $page,
            'data' => $items,
            'total' => $total,
            'from' => [] === $items ? null : $offset + 1,
            'to' => [] === $items ? null : $offset + count($items),
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'path' => $path,
            'first_page_url' => $url(1),
            'last_page_url' => $url($lastPage),
            'next_page_url' => $page < $lastPage ? $url($page + 1) : null,
            'prev_page_url' => $page > 1 ? $url(min($page - 1, $lastPage)) : null,
        ];
    }
}
