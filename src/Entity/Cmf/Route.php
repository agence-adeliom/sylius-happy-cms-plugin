<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RouteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Cmf\Bundle\RoutingBundle\Model\Route as RouteModel;

#[ORM\MappedSuperclass(repositoryClass: RouteRepository::class)]
class Route extends RouteModel implements RouteInterface
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    protected string $name = '';

    #[ORM\Column(type: Types::INTEGER)]
    protected int $position = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastModification = null;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

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

    public function getLastModification(): ?\DateTimeInterface
    {
        return $this->lastModification;
    }

    public function setLastModification(?\DateTimeInterface $lastModification): void
    {
        $this->lastModification = $lastModification;
    }

    public function getRouteKey(): string
    {
        return $this->getName();
    }
}
