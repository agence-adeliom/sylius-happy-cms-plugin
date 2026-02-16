<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaFileRenamed;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Rename
{
    /**
     * rename item.
     */
    public function renameItem(Request $request): JsonResponse
    {
        // CSRF Protection
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'new_filename' => '',
            ]);
        }

        $message = '';
        $new_filename = '';
        $content = $request->getContent();
        if (is_string($content)) {
            /** @var array{
             *    new_filename: string,
             *    file: array{
             *      id: int,
             *      type: string
             *    }
             * } $data */
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

            $file = $data['file'];
            $type = $file['type'];
            $new_filename = $this->helper->cleanName($data['new_filename'], 'folder' === $type);

            try {
                $object = 'folder' === $type ? $this->manager->getFolder($file['id']) : $this->manager->getMedia($file['id']);
                if (null === $object) {
                    throw new \Exception('File not found');
                }
                $old_filename = $object->getName();
                if (null === $old_filename) {
                    throw new \Exception('Old filename is null');
                }
                $object->setName($new_filename);
                $this->manager->save($object);
                $this->eventDispatcher->dispatch(
                    new MediaFileRenamed($old_filename, $new_filename),
                    MediaFileRenamed::NAME,
                );
            } catch (\Exception $exception) {
                $message = $exception->getMessage();
            }
        }

        return new JsonResponse(['message' => $message, 'new_filename' => $new_filename]);
    }
}
