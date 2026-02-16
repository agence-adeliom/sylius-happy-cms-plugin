<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module;

use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\FolderAlreadyExist;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait NewFolder
{
    /**
     * create new folder.
     */
    public function createNewFolder(Request $request): JsonResponse
    {
        // CSRF Protection
        try {
            $this->validateCsrfToken($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'new_folder_name' => '',
            ]);
        }

        /** @var array{
         *    folder: int|null,
         *    new_folder_name: string
         * } $data */
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $currentFolderId = $data['folder'];
        $currentFolder = empty($currentFolderId) ? null : $this->manager->getFolder($currentFolderId);

        $new_folder_name = $this->helper->cleanName($data['new_folder_name'], true);
        $message = '';

        try {
            $folder = $this->manager->createFolder($new_folder_name, $currentFolder?->getPath());
        } catch (FolderAlreadyExist $alreadyExist) {
            $message = $alreadyExist->getMessage();
        } catch (\Exception|FilesystemException $exception) {
            $message = $this->translator->trans('error.creating_dir', [], 'SyliusHappyCMSPlugin');
        }

        return new JsonResponse(['message' => $message, 'new_folder_name' => $new_folder_name]);
    }
}
