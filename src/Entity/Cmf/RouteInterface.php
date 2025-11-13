<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Cmf;

use Sylius\Resource\Model\ResourceInterface;

interface RouteInterface extends ResourceInterface
{
    public function setName(string $name): static;

    public function getLastModification(): ?\DateTimeInterface;

    public function setLastModification(?\DateTimeInterface $lastModification): void;

    public function getRouteKey(): string;

    public function isPreview(): bool;
}
