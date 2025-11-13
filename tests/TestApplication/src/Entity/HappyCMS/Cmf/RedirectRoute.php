<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute as BaseRedirectRoute;
use Tests\Adeliom\SyliusHappyCMSPlugin\Repository\HappyCMS\Cmf\RedirectRouteRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('ALL')]
#[ORM\Entity(repositoryClass: RedirectRouteRepository::class)]
#[ORM\Table(name: 'sylius_happy_cms__redirect_route')]
class RedirectRoute extends BaseRedirectRoute
{
}
