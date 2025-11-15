<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Sylius\Resource\Model\ResourceInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

interface RouteInterface extends ResourceInterface, RouteObjectInterface
{
    public function setName(string $name): static;

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
}
