<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityNameSlugTrait;
use Adeliom\SyliusHappyCMSPlugin\Traits\Seo\EntitySeoTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreRemove;
use Doctrine\ORM\Mapping\PreUpdate;
use JMS\Serializer\Annotation as Serializer;
use Sylius\Resource\Model\AbstractTranslation;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
class PageTranslation extends AbstractTranslation implements PageTranslationInterface
{
    use EntityIdTrait;
    use EntityNameSlugTrait;
    use EntitySeoTrait {
        EntitySeoTrait::__construct as private SEOConstruct;
    }

    #[Groups('Default')]
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    #[Serializer\Expose]
    #[Serializer\Type('integer')]
    #[Serializer\Groups(['Detailed', 'Default', 'Autocomplete'])]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @var array<string, mixed>|null $content */
    #[Groups('main')]
    #[Column(name: 'content', type: Types::JSON, nullable: true)]
    #[Assert\Type('array')]
    protected ?array $content = [];

    public function __construct()
    {
        $this->SEOConstruct();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContent(): ?array
    {
        return $this->content;
    }

    /**
     * @param array<string, mixed>|null $content
     */
    public function setContent(?array $content): void
    {
        $this->content = $content;
    }

    public function getTree(string $separator = '/', bool $name = false): string
    {
        $tree = '';

        $current = $this;
        do {
            $slug = (is_object($current) && method_exists($current, 'getPageSlug')) ? $current->getPageSlug() : $current->getSlug();
            $tree = $name ? $current->getName() . $separator . $tree : $slug . $separator . $tree;
            if (null !== $current->getTranslatable()) {
                $current = $current->getTranslatable()->getParent() ?? null;
            } else {
                $current = null;
            }
        } while ($current);

        return trim($tree, $separator);
    }

    public function getTreeDisplay(): string
    {
        $tree = ' ' . $this->getName();

        $current = $this;
        do {
            $tree = '―' . $tree;
            if (null !== $current->getTranslatable()) {
                $current = $current->getTranslatable()->getParent() ?? null;
            } else {
                $current = null;
            }
        } while ($current);

        return mb_substr($tree, 1);
    }

    #[PrePersist]
    #[PreUpdate]
    public function setSeoTitle(PrePersistEventArgs|PreUpdateEventArgs $event): void
    {
        if (empty($this->getName())) {
            // $this->setName('No name');
        }
        if (empty($this->getSEO()->getTitle())) {
            $this->getSEO()->setTitle($this->getName());
        }
    }

    #[PreRemove]
    public function onRemove(PreRemoveEventArgs $event): void
    {
        /** @var PageInterface $page */
        $page = $this->getTranslatable();
        $this->setName($this->getName() . '-' . $page->getId() . '-deleted');
        $this->setSlug($this->getSlug() . '-' . $page->getId() . '-deleted');
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
