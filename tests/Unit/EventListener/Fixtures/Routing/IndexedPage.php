<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;

/**
 * A minimal routable entity: one slug per translation, no parent page.
 * $category is a parent of another type (not routable), used to compute a parent path.
 */
#[ORM\Entity]
#[ORM\Table(name: 'indexed_page')]
class IndexedPage implements CmsRoutableInterface, TranslatableInterface
{
    use EntityRouteTrait {
        EntityRouteTrait::__construct as private initializeRoutes;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<string, IndexedPageTranslation> */
    #[ORM\OneToMany(targetEntity: IndexedPageTranslation::class, mappedBy: 'translatable', cascade: ['persist'], indexBy: 'locale')]
    private Collection $translations;

    #[ORM\Column(nullable: true)]
    public ?string $category = null;

    #[ORM\Column]
    public bool $isHomePage = false;

    public function __construct(string $slug, string $locale = 'en_US')
    {
        $this->initializeRoutes();
        $this->translations = new ArrayCollection();
        $this->addTranslation(new IndexedPageTranslation($locale, $slug));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<string, IndexedPageTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(?string $locale = null): TranslationInterface
    {
        $translation = null === $locale ? $this->translations->first() : $this->translations->get($locale);
        assert($translation instanceof IndexedPageTranslation);

        return $translation;
    }

    public function hasTranslation(TranslationInterface $translation): bool
    {
        return $this->translations->contains($translation);
    }

    public function addTranslation(TranslationInterface $translation): void
    {
        assert($translation instanceof IndexedPageTranslation);
        $translation->setTranslatable($this);
        $this->translations->set((string) $translation->getLocale(), $translation);
    }

    public function removeTranslation(TranslationInterface $translation): void
    {
        $this->translations->removeElement($translation);
    }

    public function setCurrentLocale(string $currentLocale): void
    {
    }

    public function setFallbackLocale(string $fallbackLocale): void
    {
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function previewIsAvailable(): bool
    {
        return false;
    }

    public function isStatePublished(): bool
    {
        return true;
    }

    public function isStateUnpublished(): bool
    {
        return false;
    }

    public function isStatePending(): bool
    {
        return false;
    }

    public function hasState(?string $state): bool
    {
        return false;
    }

    public function isDatePublished(): bool
    {
        return true;
    }
}
