<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface SharedBlockInterface extends ResourceInterface, TranslatableInterface
{
    public function getType(): ?string;

    public function getStatus(): bool;

    public function getKey(): ?string;

    public function getName(): ?string;
}
