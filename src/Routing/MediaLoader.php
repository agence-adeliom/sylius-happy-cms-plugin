<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class MediaLoader extends Loader
{
    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $resource = '@SyliusHappyCMSPlugin/config/media/routes.php';
        $type = 'php';

        $importedRoutes = $this->import($resource, $type);

        assert($importedRoutes instanceof RouteCollection, '$importedRoutes must be an instance of RouteCollection');

        $routes->addCollection($importedRoutes);

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'attribute';
    }
}
