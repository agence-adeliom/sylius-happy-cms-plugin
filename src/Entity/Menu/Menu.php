<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Menu;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityStatusTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\Menu\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\MappedSuperclass(repositoryClass: MenuRepository::class)]
#[ORM\UniqueConstraint(name: 'sylius_happy_cms__menu_code_unik', columns: ['code'])]
class Menu implements MenuInterface
{
    use EntityIdTrait;
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private timestampableConstruct;
    }
    use EntityStatusTrait;

    /** @var Collection<int, MenuItemInterface> */
    #[ORM\OneToMany(targetEntity: MenuItemInterface::class, mappedBy: 'menu', cascade: ['all'])]
    #[ORM\OrderBy(['lvl' => 'DESC', 'root' => 'ASC', 'lft' => 'ASC'])]
    protected Collection $items;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 30)]
    protected ?string $code = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * `SET NULL`, not `CASCADE`: the root item also references the menu (`MenuItem#menu`), and a cascading key on both
     * sides is a cycle the unit of work cannot order when the menu is removed with its items. Deleting the root item
     * leaves the menu without root instead of deleting it.
     */
    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, cascade: ['persist', 'detach'])]
    #[ORM\JoinColumn(name: 'root_item_id', nullable: true, onDelete: 'SET NULL')]
    protected ?MenuItemInterface $rootItem = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->timestampableConstruct();
        $this->items = new ArrayCollection();
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return Collection<int, MenuItemInterface> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(MenuItemInterface $item): void
    {
        $this->items->add($item);
        if ($item->getMenu() !== $this) {
            $item->setMenu($this);
        }
    }

    public function removeItem(MenuItemInterface $item): void
    {
        $this->items->removeElement($item);
        $item->setMenu(null);
    }

    #[ORM\PreRemove]
    public function onRemove(): void
    {
        $this->setStatus();
    }

    public function getCode(): string
    {
        return $this->code ?? '';
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getRootItem(): ?MenuItemInterface
    {
        return $this->rootItem;
    }

    public function setRootItem(?MenuItemInterface $rootItem): void
    {
        $this->rootItem = $rootItem;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
