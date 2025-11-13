<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\Route as BaseRoute;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Cmf\RouteRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('ALL')]
#[ORM\Entity(repositoryClass: RouteRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__route')]
class Route extends BaseRoute
{
}
