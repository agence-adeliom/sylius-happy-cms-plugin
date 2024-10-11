<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

#[Gedmo\Tree(type: 'nested')]
#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass(repositoryClass: MenuItemRepository::class)]
class MenuItem implements MenuItemInterface
{
    use EntityIdTrait;
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private timestampableConstruct;
    }
    use EntityPublishableTrait {
        EntityPublishableTrait::__construct as private publishableConstruct;
    }
    use TranslatableTrait {
        TranslatableTrait::__construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }

    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Gedmo\TreeLeft]
    protected ?int $lft = null;

    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Gedmo\TreeLevel]
    protected ?int $lvl = null;

    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Gedmo\TreeRight]
    protected ?int $rgt = null;

    #[ORM\Column(name: 'root', type: Types::INTEGER, nullable: true)]
    #[Gedmo\TreeRoot]
    protected ?int $root = null;

    #[ORM\ManyToOne(targetEntity: MenuInterface::class, inversedBy: 'items')]
    protected ?MenuInterface $menu = null;

    #[ORM\Column(name: 'class_attribute', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $classAttribute = null;

    #[ORM\Column(name: 'position', type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    protected ?int $position = null;

    #[ORM\Column(name: 'target', type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    protected ?bool $target = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, cascade: ['persist', 'detach'], inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'CASCADE')]
    protected ?MenuItemInterface $parent = null;

    /** @var Collection<int, MenuItemInterface> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: MenuItemInterface::class, cascade: ['all'])]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    protected Collection $children;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->timestampableConstruct();
        $this->publishableConstruct();
        $this->children = new ArrayCollection();
    }

    protected function createTranslation(): TranslationInterface
    {
        return new MenuItemTranslation();
    }

    public function getTranslation(?string $locale = null): MenuItemTranslation
    {
        /** @var MenuItemTranslation $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    public static function getTranslationClass(): string
    {
        return MenuItemTranslation::class;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(mixed $lft): void
    {
        $this->lft = $lft;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(mixed $lvl): void
    {
        $this->lvl = $lvl;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(mixed $rgt): void
    {
        $this->rgt = $rgt;
    }

    public function getRoot(): ?int
    {
        return $this->root;
    }

    public function setRoot(?int $root): void
    {
        $this->root = $root;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function getClassAttribute(): ?string
    {
        return $this->classAttribute;
    }

    public function setClassAttribute(?string $classAttribute): void
    {
        $this->classAttribute = $classAttribute;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isTarget(): ?bool
    {
        return $this->target;
    }

    public function setTarget(?bool $target): void
    {
        $this->target = $target;
    }

    public function getMenu(): ?MenuInterface
    {
        return $this->menu;
    }

    public function setMenu(?MenuInterface $menu): void
    {
        $this->menu = $menu;
    }

    public function getParent(): ?MenuItemInterface
    {
        return $this->parent;
    }

    public function setParent(?MenuItemInterface $parent): void
    {
        $this->parent = $parent;

        if (null !== $parent) {
            $parent->addChild($this);
        }
    }

    /**
     * Add child.
     */
    public function addChild(MenuItemInterface $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Remove child.
     */
    public function removeChild(MenuItemInterface $child): void
    {
        $this->children->removeElement($child);
    }

    /**
     * @param Collection<int, MenuItemInterface> $children
     */
    public function setChildren(Collection $children): void
    {
        $this->children = $children;
    }

    /**
     * Get children.
     *
     * @return Collection<int, MenuItemInterface>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Get only published children.
     *
     * @return Collection<int, MenuItemInterface>
     */
    public function getPublishedChildren(): Collection
    {
        return $this->children->filter(
            static fn (MenuItemInterface $child) => $child->getPublishState() == ThreeStateStatusEnum::PUBLISHED()->getValue(),
        );
    }

    #[ORM\PreRemove]
    public function onRemove(): void
    {
        $this->setPublishState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
    }

    /**
     * Has child.
     */
    public function hasChild(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Has parent.
     */
    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    /**
     * @param array<string>|null $parents
     *
     * @return array<string>
     */
    public function getParents(?array $parents = [], ?MenuItemInterface $parent = null): array
    {
        if (empty($parent)) {
            $parents[] = (string) $this;
            $parent = $this;
        }

        if (!empty($parent->getParent())) {
            $parentParent = $parent->getParent();
            $parents[] = (string) $parentParent;
            $parents = $this->getParents($parents, $parentParent);
        }

        return $parents;
    }

    public function getFlattenParents(): string
    {
        return implode(' / ', array_reverse($this->getParents()));
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
