<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface SharedBlockInterface extends ResourceInterface, TranslatableInterface
{
    public function getType(): ?string;

    public function setType(?string $type): void;

    public function getStatus(): bool;

    public function setStatus(bool $status = false): void;

    public function getKey(): ?string;

    public function setKey(?string $key): void;

    public function getName(): ?string;

    public function setName(?string $name): void;
}
