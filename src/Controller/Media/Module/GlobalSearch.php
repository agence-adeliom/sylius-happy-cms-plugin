<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait GlobalSearch
{
    /**
     * Searches the media of every folder by name (`?search=`), in one query. Without a search term,
     * every media is listed (as the EasyMedia global search did). Files matching `ignore_files` are left out.
     */
    public function globalSearch(Request $request): JsonResponse
    {
        // Any Doctrine repository of the media entity: the query is built on the entity manager.
        $mediaClass = $this->helper->getMediaClassName();
        if (!class_exists($mediaClass) || !is_a($mediaClass, MediaInterface::class, true)) {
            return new JsonResponse([]);
        }

        $query = $this->manager->em->createQueryBuilder()
            ->select('m', 'f')
            ->from($mediaClass, 'm')
            ->leftJoin('m.folder', 'f')
            ->orderBy('m.name', 'ASC')
            ->addOrderBy('m.id', 'ASC');

        $search = $request->query->get('search');
        if (is_string($search) && '' !== trim($search)) {
            $query->andWhere("m.name LIKE :search ESCAPE '!'")
                ->setParameter('search', '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)) . '%');
        }

        /** @var list<MediaInterface> $medias */
        $medias = $query->getQuery()->getResult();
        $results = [];
        foreach ($medias as $media) {
            if (!$this->ignoreFiles($media)) {
                continue;
            }

            $path = $media->getPath();
            $time = $media->getLastModified();
            $results[] = [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'type' => $media->getMime(),
                'size' => $media->getSize(),
                'path' => $this->manager->publicUrl($media),
                'download_url' => $this->manager->downloadUrl($media),
                'storage_path' => $path,
                'dir_path' => str_contains($path, '/') ? dirname($path) : '/',
                'last_modified' => $time,
                'last_modified_formated' => $this->helper->getItemTime($time),
                'metas' => $media->getMetas(),
            ];
        }

        return new JsonResponse($results);
    }
}
