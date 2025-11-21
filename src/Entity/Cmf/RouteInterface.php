<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Sylius\Resource\Model\ResourceInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

interface RouteInterface extends ResourceInterface, RouteObjectInterface
{
    public function setName(string $name): static;

    public function getName(): string;

    public function getLastModification(): ?\DateTimeInterface;

    public function setLastModification(?\DateTimeInterface $lastModification): void;

    public function getRouteKey(): string;

    public function isPreview(): bool;

    public function getOption(string $name): mixed;

    /**
     * Set the object this url points to.
     *
     * @param object $object A content object that can be persisted by the
     *                       storage layer
     */
    public function setContent(object $object): static;

    public function getPath(): string;

    public function setStaticPrefix(string $prefix): static;

    public function getStaticPrefix(): string;

    public function setDefault(string $name, mixed $default): static;

    public function setOptions(array $options): static;

    public function setHost(?string $pattern): static;

    public function setSchemes(string|array $schemes): static;

    public function setDefaults(array $defaults): static;

    public function setRequirements(array $requirements): static;

    public function setMethods(string|array $methods): static;

    public function setVariablePattern(string $variablePattern): static;

    public function setPreview(bool $preview): void;

    public function setOption(string $name, mixed $value): static;
}
