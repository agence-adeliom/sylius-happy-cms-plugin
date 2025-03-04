<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Media;

use Sylius\Resource\Model\ResourceInterface;

interface MediaInterface extends ResourceInterface
{
    public function getPath(string $separator = '/'): string;

    /** @param array<string, mixed> $metas */
    public function setMetas(array $metas): void;

    public function setSize(?int $size): void;

    public function setLastModified(?int $lastModified): void;

    public function getMime(): ?string;

    public function setMime(?string $mime = null): void;

    public function setSlug(string $slug): void;

    public function getFolder(): ?FolderInterface;

    public function setFolder(?FolderInterface $folder): void;

    public function setName(?string $name): void;

    public function getName(): ?string;

    /** @return  array<string, mixed> */
    public function getMetas(): array;

    public function getMeta(string $key, mixed $default = null): mixed;

    public function getLastModified(): ?int;

    public function getId(): ?int;

    public function getSize(): ?int;

    public function getSlug(): ?string;
}
