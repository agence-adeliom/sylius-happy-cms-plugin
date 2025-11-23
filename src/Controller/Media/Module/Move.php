<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaFileMoved;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Move
{
    /**
     * move files/folders.
     */
    public function moveItem(Request $request): JsonResponse
    {
        /**
         * @var array{
         *     destination: int,
         *     moved_files: array<int, array{
         *          id: int,
         *          name: string,
         *          type: string,
         *          storage_path: string
         * }>
         * } $data
         */
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $destinationId = $data['destination'];
        $movedFiles = $data['moved_files'];
        $destination = null;
        if (!empty($destinationId)) {
            $destination = $this->manager->getFolder($destinationId);
        }

        $result = [];
        $toBroadCast = [];

        foreach ($movedFiles as $one) {
            $id = $one['id'];
            $file_name = $one['name'];
            $file_type = $one['type'];
            $old_path = $one['storage_path'];
            $defaults = [
                'id' => $id,
                'type' => $file_type,
                'name' => $file_name,
                'old_path' => $old_path,
            ];

            $new_path = sprintf('/%s', $file_name);
            if ($destination) {
                $new_path = $destination->getPath() . $new_path;
            }

            $defaults['new_path'] = $new_path;

            try {
                if ('folder' === $file_type && ($destination && $destination->getId() === $id)) {
                    throw new \Exception($this->translator->trans('error.move_into_self', [], 'SyliusHappyCMSPlugin'));
                }

                $entity = 'folder' === $file_type ? $this->manager->getFolder($id) : $this->manager->getMedia($id);
                if ($entity) {
                    // Move
                    try {
                        if ('folder' === $file_type && $entity instanceof FolderInterface) {
                            $entity->setParent($destination);
                        } elseif ($entity instanceof MediaInterface) {
                            $entity->setFolder($destination);
                        }

                        $this->manager->save($entity);

                        $result[] = array_merge($defaults, ['success' => true]);

                        // fire event
                        $this->eventDispatcher->dispatch(
                            new MediaFileMoved(
                                $defaults['old_path'],
                                $defaults['new_path'],
                            ),
                            MediaFileMoved::NAME,
                        );
                    } catch (\Exception $exception) {
                        throw new \Exception($this->translator->trans('error.moving', [], 'SyliusHappyCMSPlugin'), $exception->getCode(), $exception);
                    }
                } else {
                    $result[] = [
                        'success' => false,
                        'message' => 'Entity not found',
                    ];
                }
            } catch (\Exception $e) {
                $result[] = [
                    'success' => false,
                    'message' => sprintf('"%s" ', $old_path) . $e->getMessage(),
                ];
            }
        }

        return new JsonResponse($result);
    }
}
