<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Page;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
use Adeliom\SyliusHappyCMSPlugin\Repository\Page\PageRepository;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Sylius\Resource\Model\TranslatableTrait;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Gedmo\Tree(type: 'nested')]
#[ORM\MappedSuperclass(repositoryClass: PageRepository::class)]
#[Serializer\ExclusionPolicy('ALL')]
class Page implements PageInterface
{
    use EntityIdTrait;
    use TranslatableTrait {
        TranslatableTrait::__construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private timestampableConstruct;
    }
    use EntityPublishableTrait {
        EntityPublishableTrait::__construct as private publishableConstruct;
    }
    use EntityRouteTrait {
        EntityRouteTrait::__construct as private entityRouteConstruct;
    }

    /** @var Collection<int, OrmRoute> */
    #[ORM\ManyToMany(targetEntity: OrmRoute::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable('sylius_happy_cms__page_route')]
    protected Collection $routes;

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

    #[ORM\Column(name: 'position', type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    protected ?int $position = null;

    #[Gedmo\TreeParent]
    #[Assert\Type(PageInterface::class)]
    #[ORM\ManyToOne(inversedBy: 'children', targetEntity: PageInterface::class)]
    #[ORM\JoinColumn(name: 'parent_id', onDelete: 'SET NULL', referencedColumnName: 'id')]
    protected ?PageInterface $parent = null;

    /** @var Collection<int, PageInterface> */
    #[ORM\OneToMany(targetEntity: PageInterface::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $children;

    #[ORM\Column(name: 'action', type: Types::STRING, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $action = null;

    #[Groups('main')]
    #[ORM\Column(name: 'template', type: Types::STRING, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $template = null;

    #[ORM\Column(name: 'css', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $css = null;

    #[ORM\Column(name: 'js', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $js = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->initializeTranslationsCollection();
        $this->timestampableConstruct();
        $this->publishableConstruct();
        $this->entityRouteConstruct();
    }

    protected function createTranslation(): PageTranslationInterface
    {
        return new PageTranslation();
    }

    public function getTranslation(?string $locale = null): PageTranslationInterface
    {
        $translation = $this->doGetTranslation($locale);
        if (!$translation instanceof PageTranslationInterface) {
            throw new \RuntimeException('PageInterface must return a PageTranslationInterface translation');
        }

        return $translation;
    }

    public static function getTranslationClass(): string
    {
        return PageTranslation::class;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function setParent(?PageInterface $parent = null): void
    {
        if ($parent === $this) {
            // Refuse the category to have itself as parent.
            $this->parent = null;

            return;
        }

        $this->parent = $parent;

        // Ensure bidirectional relation is respected.
        if ($parent && false === $parent->getChildren()->indexOf($this)) {
            $parent->addChildren($this);
        }
    }

    public function getParent(): ?PageInterface
    {
        return $this->parent;
    }

    /**
     * @return Collection<int, PageInterface>
     */
    public function getChildren(): Collection
    {
        return !isset($this->children) ? new ArrayCollection() : $this->children;
    }

    public function addChildren(PageInterface $page): void
    {
        if (!isset($this->children)) {
            $this->children = new ArrayCollection();
        }
        $this->children->add($page);

        if ($page->getParent() !== $this) {
            $page->setParent($this);
        }
    }

    public function removeChildren(PageInterface $page): void
    {
        $this->children->removeElement($page);
    }

    public function isHomepage(): bool
    {
        return PageInterface::HOMEPAGE == $this->template;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function setCss(string $css): void
    {
        $this->css = $css;
    }

    public function getJs(): ?string
    {
        return $this->js;
    }

    public function setJs(string $js): void
    {
        $this->js = $js;
    }

    #[ORM\PreRemove]
    public function onRemove(PreRemoveEventArgs $event): void
    {
        $em = $event->getObjectManager();
        if (count($this->children)) {
            foreach ($this->children as $child) {
                $child->setParent();
                $em->persist($child);
            }
        }

        $this->setPublishState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
        $this->parent = null;
    }

    /**
     * @return Collection<int, PageTranslationInterface>
     */
    public function getSeoTranslations(): Collection
    {
        return $this->translations;
    }

    /**
     * @return Collection<int, PageTranslationInterface>
     */
    public function getSlugTranslations(): Collection
    {
        return $this->translations;
    }

    #[Serializer\Expose]
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('slug')]
    #[Serializer\Type('string')]
    #[Serializer\Groups(['Autocomplete'])]
    public function getSlug(): ?string
    {
        return $this->getTranslation()->getSlug();
    }

    #[Serializer\Expose]
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('name')]
    #[Serializer\Type('string')]
    #[Serializer\Groups(['Autocomplete'])]
    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
