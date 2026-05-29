<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('media.index', '/admin/happy-cms-media/medias/')
        ->controller([MediaController::class, 'index'])
        ->methods(['GET']);

    $routes->add('media.browse', '/admin/happy-cms-media/medias/browse')
        ->controller([MediaController::class, 'browse'])
        ->methods(['GET']);

    $routes->add('media.upload', '/admin/happy-cms-media/medias/upload')
        ->controller([MediaController::class, 'upload'])
        ->methods(['POST']);

    $routes->add('media.uploadCropped', '/admin/happy-cms-media/medias/upload-cropped')
        ->controller([MediaController::class, 'uploadEditedImage'])
        ->methods(['POST']);

    $routes->add('media.uploadLink', '/admin/happy-cms-media/medias/upload-link')
        ->controller([MediaController::class, 'uploadLink'])
        ->methods(['POST']);

    $routes->add('media.get_files', '/admin/happy-cms-media/medias/get-files')
        ->controller([MediaController::class, 'getFiles'])
        ->methods(['POST']);

    $routes->add('media.get_file_info', '/admin/happy-cms-media/medias/get-file-info')
        ->controller([MediaController::class, 'getItemInfos'])
        ->methods(['POST']);

    $routes->add('media.new_folder', '/admin/happy-cms-media/medias/create-new-folder')
        ->controller([MediaController::class, 'createNewFolder'])
        ->methods(['POST']);

    $routes->add('media.delete_file', '/admin/happy-cms-media/medias/delete-file')
        ->controller([MediaController::class, 'deleteItem'])
        ->methods(['POST']);

    $routes->add('media.move_file', '/admin/happy-cms-media/medias/move-file')
        ->controller([MediaController::class, 'moveItem'])
        ->methods(['POST']);

    $routes->add('media.rename_file', '/admin/happy-cms-media/medias/rename-file')
        ->controller([MediaController::class, 'renameItem'])
        ->methods(['POST']);

    $routes->add('media.edit_metas_file', '/admin/happy-cms-media/medias/edit-metas-file')
        ->controller([MediaController::class, 'editMetasItem'])
        ->methods(['POST']);

    $routes->add('media.generate_alt_file', '/admin/happy-cms-media/medias/generate-alt-file')
        ->controller([MediaController::class, 'generateAltItem'])
        ->methods(['POST']);

    $routes->add('media.generate_alt_group', '/admin/happy-cms-media/medias/generate-alt-group')
        ->controller([MediaController::class, 'generateAltGroup'])
        ->methods(['POST']);

    $routes->add('media.generate_all_alt', '/admin/happy-cms-media/medias/generate-all-alt')
        ->controller([MediaController::class, 'generateAllAlt'])
        ->methods(['POST']);

    $routes->add('media.global_search', '/admin/happy-cms-media/medias/global-search')
        ->controller([MediaController::class, 'globalSearch'])
        ->methods(['GET']);

    $routes->add('media.folder_download', '/admin/happy-cms-media/medias/folder-download')
        ->controller([MediaController::class, 'downloadFolder'])
        ->methods(['POST']);

    $routes->add('media.files_download', '/admin/happy-cms-media/medias/files-download')
        ->controller([MediaController::class, 'downloadFiles'])
        ->methods(['POST']);
};
