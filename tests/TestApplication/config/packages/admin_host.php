<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Adeliom\SyliusHappyCMSPlugin\Config\EnvVarResource;

// Host of the admin routes (see config/routes.yaml). Routes cannot read %env()%, so the value is read here.
// Empty by default: the admin answers on any host, under /admin.
return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
    $adminHost = EnvVarResource::read('ADMIN_HOST');

    $container->parameters()->set('admin_host', $adminHost);
    $builder->addResource(new EnvVarResource('ADMIN_HOST', $adminHost));
};
