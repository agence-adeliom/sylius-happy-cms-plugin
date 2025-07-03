<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepository;
use Symfony\Cmf\Bundle\RoutingBundle\Model\Route as RouteModel;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass(repositoryClass: RouteRepository::class)]
class Route extends RouteModel implements RouteInterface
{
    #[ORM\Id]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, unique: true)]
    protected string $name = '';

    /**
     * Sort order of this route when it is returned by the route provider.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $position = 0;

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the sort order of this route.
     */
    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the sort order of this route.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function getRouteKey(): string
    {
        return $this->getName();
    }
}
