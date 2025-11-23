<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaFileDeleted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Delete
{
    /**
     * delete files/folders.
     */
    public function deleteItem(Request $request): JsonResponse
    {
        /** @var array{
         *     deleted_files: array<int, array{
         *          id: int,
         *          name: string,
         *          type: string,
         *          storage_path: string
         *      }>
         *  } $data
         */
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $result = [];

        foreach ($data['deleted_files'] as $one) {
            $id = $one['id'];
            $name = $one['name'];
            $type = $one['type'];
            $item_path = $one['storage_path'];
            $defaults = [
                'id' => $id,
                'name' => $name,
                'type' => $type,
                'path' => $item_path,
            ];

            try {
                $entity = 'folder' === $type ? $this->manager->getFolder($id) : $this->manager->getMedia($id);

                if ($entity instanceof MediaInterface || $entity instanceof FolderInterface) {
                    $this->manager->delete($entity);

                    $result[] = array_merge($defaults, ['success' => true]);
                    $toBroadCast[] = $defaults;

                    $this->eventDispatcher->dispatch(new MediaFileDeleted($item_path, 'folder' === $type), MediaFileDeleted::NAME);
                }
            } catch (\Exception) {
                $result[] = array_merge($defaults, [
                    'success' => false,
                    'message' => $this->translator->trans('error.deleting_file', [], 'SyliusHappyCMSPlugin'),
                ]);
            }
        }

        return new JsonResponse($result);
    }
}
