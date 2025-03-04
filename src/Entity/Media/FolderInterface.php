<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Media;

use Sylius\Resource\Model\ResourceInterface;

interface FolderInterface extends ResourceInterface
{
    public function setParent(?self $parent = null): void;

    public function getPath(string $separator = '/'): string;

    public function setName(string $name): void;

    public function getName(): ?string;

    public function getSlug(): ?string;
}
