<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Media;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaGenerateAllAlt;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaGenerateAlt;
use Adeliom\SyliusHappyCMSPlugin\Event\Media\MediaGenerateAltGroup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait Metas
{
    /**
     * rename item.
     */
    public function editMetasItem(Request $request): JsonResponse
    {
        /** @var array{
         *    file: array{
         *      id: int
         *    },
         *    new_metas: array<string, string>
         * } $data
         */
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $file = $data['file'];
        $metas = $data['new_metas'];
        $message = '';

        try {
            /** @var Media $object */
            $object = $this->manager->getMedia($file['id']);
            $object->setMetas(array_merge($object->getMetas(), $metas));
            $this->manager->save($object);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
        }

        return new JsonResponse(['message' => $message, 'metas' => $metas]);
    }

    /**
     * Dispatch an event to allow to generate an alt for the selected file
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAltItem(Request $request): JsonResponse
    {
        /** @var array{
         *    file: array{
         *      id: int
         *    },
         *    path: ?string
         * } $data
         */
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $file = $data['file'];
        $error = '';
        $oldAlt = '';

        try {
            /** @var ?MediaInterface $object */
            $object = $this->manager->getMedia($file['id']);
            if ($object instanceof MediaInterface) {
                $metas = $object->getMetas();
                $oldAlt = $metas['alt'];
                $event = $this->eventDispatcher->dispatch(
                    new MediaGenerateAlt($object, $data['path'] ?? '', $oldAlt),
                    MediaGenerateAlt::NAME,
                );
                $newAlt = $event->getAlt();
                if (!empty($newAlt) && $newAlt !== $oldAlt) {
                    $metas['alt'] = $newAlt;
                    $object->setMetas(array_merge($object->getMetas(), $metas));
                    $this->manager->save($object);

                    return new JsonResponse(['error' => $error, 'alt' => $newAlt]);
                }
            } else {
                $error = 'Media not found';
            }
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
        }

        return new JsonResponse(['error' => $error, 'alt' => $oldAlt]);
    }

    /**
     * Dispatch an event to allow to generate all alt for a group of medias
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAltGroup(Request $request): JsonResponse
    {
        try {
            /**
             * @var array{
             *     files: array<int, array{
             *      id: int,
             *      path: string
             *      }>
             * } $files
             */
            $files = json_decode($request->getContent(), true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
            $this->eventDispatcher->dispatch(
                new MediaGenerateAltGroup($files['files']),
                MediaGenerateAltGroup::NAME,
            );

            return new JsonResponse(['error' => null, 'data' => 'generating']);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'data' => '']);
        }
    }

    /**
     * Dispatch an event to allow to generate all alt for all media
     *
     * @param Request $request the AJAX request on submit
     */
    public function generateAllAlt(Request $request): JsonResponse
    {
        try {
            $this->eventDispatcher->dispatch(new MediaGenerateAllAlt($request), MediaGenerateAllAlt::NAME);

            return new JsonResponse(['error' => null, 'data' => 'generating']);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'data' => '']);
        }
    }
}
