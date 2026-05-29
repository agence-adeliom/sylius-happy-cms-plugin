<?php

declare(strict_types=1);

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('media.file_download', '/media/download/{path}')
        ->controller([MediaController::class, 'downloadFile'])
        ->methods(['GET'])
        ->requirements(['path' => '.+']);
};
