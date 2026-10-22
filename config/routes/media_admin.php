<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Media manager routes, without any path prefix, on the path of the
 * `sylius_happy_cms.media.admin_path` option (`happy-cms-media/medias` by default).
 *
 * The plugin `config/routes.yaml` imports this file under the Sylius admin path
 * (`/%sylius_admin.path_name%`). Import this file directly when your admin is
 * served from another prefix or from its own host.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('media.index', '/%sylius_happy_cms.media.admin_path%/')
        ->controller(MediaController::class . '::index')
        ->methods(['GET']);

    $routes->add('media.browse', '/%sylius_happy_cms.media.admin_path%/browse')
        ->controller(MediaController::class . '::browse')
        ->methods(['GET']);

    $routes->add('media.upload', '/%sylius_happy_cms.media.admin_path%/upload')
        ->controller(MediaController::class . '::upload')
        ->methods(['POST']);

    $routes->add('media.uploadCropped', '/%sylius_happy_cms.media.admin_path%/upload-cropped')
        ->controller(MediaController::class . '::uploadEditedImage')
        ->methods(['POST']);

    $routes->add('media.uploadLink', '/%sylius_happy_cms.media.admin_path%/upload-link')
        ->controller(MediaController::class . '::uploadLink')
        ->methods(['POST']);

    $routes->add('media.get_files', '/%sylius_happy_cms.media.admin_path%/get-files')
        ->controller(MediaController::class . '::getFiles')
        ->methods(['POST']);

    $routes->add('media.get_file_info', '/%sylius_happy_cms.media.admin_path%/get-file-info')
        ->controller(MediaController::class . '::getItemInfos')
        ->methods(['POST']);

    $routes->add('media.new_folder', '/%sylius_happy_cms.media.admin_path%/create-new-folder')
        ->controller(MediaController::class . '::createNewFolder')
        ->methods(['POST']);

    $routes->add('media.delete_file', '/%sylius_happy_cms.media.admin_path%/delete-file')
        ->controller(MediaController::class . '::deleteItem')
        ->methods(['POST']);

    $routes->add('media.move_file', '/%sylius_happy_cms.media.admin_path%/move-file')
        ->controller(MediaController::class . '::moveItem')
        ->methods(['POST']);

    $routes->add('media.rename_file', '/%sylius_happy_cms.media.admin_path%/rename-file')
        ->controller(MediaController::class . '::renameItem')
        ->methods(['POST']);

    $routes->add('media.edit_metas_file', '/%sylius_happy_cms.media.admin_path%/edit-metas-file')
        ->controller(MediaController::class . '::editMetasItem')
        ->methods(['POST']);

    $routes->add('media.generate_alt_file', '/%sylius_happy_cms.media.admin_path%/generate-alt-file')
        ->controller(MediaController::class . '::generateAltItem')
        ->methods(['POST']);

    $routes->add('media.generate_alt_group', '/%sylius_happy_cms.media.admin_path%/generate-alt-group')
        ->controller(MediaController::class . '::generateAltGroup')
        ->methods(['POST']);

    $routes->add('media.generate_all_alt', '/%sylius_happy_cms.media.admin_path%/generate-all-alt')
        ->controller(MediaController::class . '::generateAllAlt')
        ->methods(['POST']);

    $routes->add('media.global_search', '/%sylius_happy_cms.media.admin_path%/global-search')
        ->controller(MediaController::class . '::globalSearch')
        ->methods(['GET']);

    $routes->add('media.folder_download', '/%sylius_happy_cms.media.admin_path%/folder-download')
        ->controller(MediaController::class . '::downloadFolder')
        ->methods(['POST']);

    $routes->add('media.files_download', '/%sylius_happy_cms.media.admin_path%/files-download')
        ->controller(MediaController::class . '::downloadFiles')
        ->methods(['POST']);
};
