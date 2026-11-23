<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indexed_route')]
class IndexedRoute extends Route
{
}
