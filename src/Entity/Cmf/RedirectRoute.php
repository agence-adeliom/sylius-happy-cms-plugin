<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RedirectRouteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Cmf\Bundle\RoutingBundle\Model\RedirectRoute as RedirectRouteModel;
use Symfony\Component\Routing\Route as SymfonyRoute;

#[ORM\MappedSuperclass(repositoryClass: RedirectRouteRepository::class)]
class RedirectRoute extends RedirectRouteModel implements RedirectRouteInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * Absolute uri to redirect to.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $uri = null;

    /**
     * The name of a target route (for use with standard symfony routes).
     */
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: true)]
    protected ?string $routeName = null;

    /**
     * Target route document to redirect to different dynamic route.
     */
    #[ORM\ManyToOne(targetEntity: RouteInterface::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'routeTargetId', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?SymfonyRoute $routeTarget = null;

    /**
     * Whether this is a permanent redirect. Defaults to false.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $permanent = false;

    protected array $parameters = [];

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(?string $uri): void
    {
        $this->uri = $uri;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getRouteTarget(): ?SymfonyRoute
    {
        return $this->routeTarget;
    }

    public function setRouteTarget(?SymfonyRoute $routeTarget): void
    {
        $this->routeTarget = $routeTarget;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): void
    {
        $this->permanent = $permanent;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
