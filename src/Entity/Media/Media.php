<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Media;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\MappedSuperclass]
class Media implements MediaInterface, \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(length: 100)]
    protected ?string $slug = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $mime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $size = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $lastModified = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    protected array $metas = [];

    #[ORM\ManyToOne(targetEntity: FolderInterface::class, inversedBy: 'medias')]
    protected ?FolderInterface $folder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;

        if (!$this->slug) {
            $this->slug = (new AsciiSlugger())->slug(strtolower((string) $this->name))->toString();
        }
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime = null): void
    {
        $this->mime = $mime;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    public function setLastModified(?int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metas[$key] ?? $default;
    }

    public function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    public function getFolder(): ?FolderInterface
    {
        return $this->folder;
    }

    public function setFolder(?FolderInterface $folder): void
    {
        $this->folder = $folder;
    }

    public function getPath(string $separator = '/'): string
    {
        $tree = $this->getSlug();
        $current = $this->getFolder();
        if (null !== $current) {
            do {
                $tree = $current->getSlug() . $separator . $tree;
                $current = $current->getParent();
            } while ($current);
        }

        return trim($tree, $separator);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
